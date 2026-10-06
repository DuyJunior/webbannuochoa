<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmailVerificationCodeService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    public function notice(Request $request, EmailVerificationCodeService $codes)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('welcome');
        }

        return view('auth.verify-email', ['retryAfter' => $codes->retryAfter($request->user())]);
    }

    public function confirm(Request $request, EmailVerificationCodeService $codes): RedirectResponse
    {
        $data = $request->validate(['code' => ['bail', 'required', 'string', 'regex:/\A[0-9]{6}\z/']], [
            'code.required' => __('Vui lòng nhập mã OTP gồm 6 chữ số.'),
            'code.string' => __('Vui lòng nhập mã OTP gồm 6 chữ số.'),
            'code.regex' => __('Vui lòng nhập mã OTP gồm 6 chữ số.'),
        ]);
        $result = $codes->verify($request->user(), $data['code']);
        if ($result === 'verified') {
            event(new Verified($request->user()->fresh()));
            $request->session()->regenerate();
        }
        if (in_array($result, ['verified', 'already_verified'], true)) {
            return redirect()->intended(route('welcome'))->with('success', __('Xác thực email thành công. Chào mừng bạn đến với Soopi!'));
        }

        return back()->withErrors(['code' => match ($result) {
            'expired' => __('Mã OTP đã hết hạn hoặc chưa được gửi. Vui lòng yêu cầu mã mới.'),
            'locked' => __('Bạn đã nhập sai 5 lần. Vui lòng yêu cầu mã OTP mới.'),
            default => __('Mã OTP chưa đúng. Vui lòng kiểm tra lại email.'),
        }]);
    }

    public function resend(Request $request, EmailVerificationCodeService $codes): RedirectResponse
    {
        try {
            $result = $codes->send($request->user());
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['delivery' => __('Chưa thể gửi mã OTP. Vui lòng thử lại sau ít phút.')]);
        }
        if ($result === 'verified') {
            return redirect()->route('welcome');
        }
        if ($result === 'cooldown') {
            return back()->withErrors(['delivery' => __('Vui lòng đợi :seconds giây trước khi gửi lại mã.', ['seconds' => $codes->retryAfter($request->user())])]);
        }

        return back()->with('message', __('Đã gửi mã OTP mới. Vui lòng kiểm tra hộp thư và dùng mã mới nhất.'));
    }

    // Keep previously issued signed links valid; all new emails use OTP.
    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        $alreadyVerified = $user->hasVerifiedEmail();
        if (! $alreadyVerified && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', $alreadyVerified
            ? __('Email của bạn đã được xác thực. Vui lòng đăng nhập để tiếp tục.')
            : __('Xác thực email thành công! Vui lòng quay lại trang đăng nhập để tiếp tục.'));
    }
}
