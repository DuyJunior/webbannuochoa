<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;

class PasswordResetController extends Controller
{
    private const REQUEST_MESSAGE = 'Nếu email này thuộc một tài khoản, Soopi sẽ gửi liên kết đặt lại mật khẩu. Hãy kiểm tra cả thư mục spam; bạn có thể yêu cầu lại sau một phút.';

    private const INVALID_LINK = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu liên kết mới.';

    public function requestForm(): Response
    {
        return $this->privateView('auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'string', 'email', 'max:255']], [
            'email.required' => __('Vui lòng nhập email tài khoản.'),
            'email.email' => __('Vui lòng nhập địa chỉ email hợp lệ.'),
        ]);

        try {
            Password::sendResetLink($credentials, function (User $user, string $token): void {
                $user->notify(new ResetPasswordNotification($token));
            });
        } catch (Throwable $exception) {
            // Provider/queue errors must not disclose an account or log a reset token.
            Log::warning('Password recovery could not be queued.', ['exception_class' => $exception::class]);
        }

        return redirect()->route('password.request')->with('status', __(self::REQUEST_MESSAGE))
            ->withInput($credentials)->header('Cache-Control', 'no-store, private');
    }

    public function resetForm(Request $request, string $token): Response
    {
        $email = is_string($request->query('email')) ? $request->query('email') : '';
        $user = strlen($email) <= 255 && filter_var($email, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $email)->first() : null;
        $validToken = strlen($token) === 64 && ctype_xdigit($token)
            && $user && Password::tokenExists($user, $token);

        return $this->privateView('auth.reset-password', [
            'email' => $email, 'token' => $token, 'validToken' => (bool) $validToken,
            'invalidLinkMessage' => __(self::INVALID_LINK),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->only('email', 'token', 'password', 'password_confirmation'), [
            'token' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]{64}\z/'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'password.required' => __('Vui lòng nhập mật khẩu mới.'),
            'password.string' => __('Vui lòng nhập mật khẩu hợp lệ.'),
            'password.confirmed' => __('Mật khẩu nhập lại chưa khớp.'),
            'password.min' => __('Mật khẩu cần có ít nhất 8 ký tự.'),
            'password.password.letters' => __('Mật khẩu cần có ít nhất một chữ cái.'),
            'password.password.numbers' => __('Mật khẩu cần có ít nhất một chữ số.'),
        ]);

        if ($validator->fails()) {
            if ($validator->errors()->has('token') || $validator->errors()->has('email')) {
                return redirect()->route('password.request')->withErrors(['email' => __(self::INVALID_LINK)]);
            }

            // Keep secrets out of flashed input; only the reset URL carries the token.
            return redirect()->route('password.reset', $request->only('token', 'email'))
                ->withErrors($validator)->withInput($request->only('email'));
        }

        $data = $validator->validated();
        $status = DB::transaction(function () use ($data): string {
            // Serialize validation, password change and broker token consumption for this account.
            User::where('email', $data['email'])->lockForUpdate()->first();

            return Password::reset($data, function (User $user, string $password): void {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                if (config('session.driver') === 'database') {
                    DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                        ->where('user_id', $user->id)->delete();
                }
                event(new PasswordReset($user));
            });
        }, 3);

        if ($status !== Password::PASSWORD_RESET) {
            return redirect()->route('password.request')->withErrors(['email' => __(self::INVALID_LINK)]);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', __('Đã đặt lại mật khẩu. Vui lòng đăng nhập bằng mật khẩu mới.'));
    }

    private function privateView(string $view, array $data = []): Response
    {
        return response()->view($view, $data)->withHeaders([
            'Cache-Control' => 'no-store, private',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
