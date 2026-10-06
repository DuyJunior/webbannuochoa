<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Hiển thị form đăng ký
    public function showRegistrationForm()
    {
        if (Auth::check()) {
            if (Auth::user()->canManageLivestreams()) {
                return redirect()->route(Auth::user()->role === 'admin' ? 'admin.dashboard' : 'admin.livestreams.index');
            }
            if (! Auth::user()->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            return redirect()->route('home');
        }

        return view('auth.register');
    }

    // Xử lý đăng ký người dùng
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'bail|required|string|max:255',
            'email' => 'bail|required|string|max:255|email|unique:users,email',
            'password' => 'bail|required|string|confirmed|min:6',
        ], [
            'name.required' => __('Vui lòng nhập họ và tên.'),
            'name.string' => __('Họ và tên phải là văn bản.'),
            'name.max' => __('Họ và tên không được dài quá 255 ký tự.'),
            'email.required' => __('Vui lòng nhập địa chỉ email.'),
            'email.string' => __('Vui lòng nhập địa chỉ email hợp lệ.'),
            'email.max' => __('Địa chỉ email không được dài quá 255 ký tự.'),
            'email.unique' => __('Email này đã được đăng ký. Vui lòng đăng nhập; nếu chưa xác thực, bạn có thể yêu cầu mã OTP mới.'),
            'email.email' => __('Vui lòng nhập địa chỉ email hợp lệ.'),
            'password.required' => __('Vui lòng nhập mật khẩu.'),
            'password.string' => __('Vui lòng nhập mật khẩu hợp lệ.'),
            'password.confirmed' => __('Mật khẩu nhập lại chưa khớp.'),
            'password.min' => __('Mật khẩu cần có ít nhất 6 ký tự.'),
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => 'user', // Mặc định là user
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('verification.notice')
                ->withErrors(['delivery' => __('Tài khoản đã được tạo nhưng chưa thể gửi mã OTP. Vui lòng bấm gửi lại mã.')]);
        }

        return redirect()->route('verification.notice')
            ->with('success', __('Đã gửi mã OTP. Kiểm tra hộp thư để hoàn tất đăng ký.'));
    }

    // Hiển thị form đăng nhập
    public function showLoginForm()
    {
        if (Auth::check()) {
            if (Auth::user()->canManageLivestreams()) {
                return redirect()->route(Auth::user()->role === 'admin' ? 'admin.dashboard' : 'admin.livestreams.index');
            }
            if (! Auth::user()->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            return redirect()->route('home');
        }

        return view('auth.login');
    }

    // Xử lý đăng nhập người dùng
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'bail|required|string|max:255|email',
            'password' => 'bail|required|string',
        ], $this->loginValidationMessages());

        if (Auth::attempt($request->only('email', 'password'))) {
            $request->session()->regenerate();

            if (Auth::user()->canManageLivestreams()) {
                return redirect()->intended(route(Auth::user()->role === 'admin' ? 'admin.dashboard' : 'admin.livestreams.index'))
                    ->with('success', __('Đăng nhập thành công.'));
            }

            if (! Auth::user()->hasVerifiedEmail()) {
                return redirect()->route('verification.notice')
                    ->with('message', __('Tài khoản của bạn chưa xác thực email. Nhập mã OTP hoặc bấm gửi lại mã bên dưới.'));
            }

            return redirect()->intended(route('welcome'))
                ->with('success', __('Đăng nhập thành công! Chào mừng ').Auth::user()->name.' quay trở lại.');
        }

        return back()->withErrors([
            'email' => __('Email hoặc mật khẩu không đúng.'),
        ])->withInput($request->only('email'));
    }

    // Xử lý đăng xuất
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    // ==========================================
    // CÁC HÀM DÀNH RIÊNG CHO TRANG ADMIN LOGIN
    // ==========================================

    // Hiển thị form đăng nhập Admin
    public function showAdminLoginForm()
    {
        if (Auth::check()) {
            if (Auth::user()->canManageLivestreams()) {
                return redirect()->route(Auth::user()->role === 'admin' ? 'admin.dashboard' : 'admin.livestreams.index');
            }

            return redirect()->route('home')->with('error', __('Bạn không có quyền truy cập vào trang quản trị viên!'));
        }

        return view('admin.login');
    }

    // Xử lý đăng nhập Admin
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'bail|required|string|max:255|email',
            'password' => 'bail|required|string',
        ], $this->loginValidationMessages());

        if (Auth::attempt($request->only('email', 'password'))) {
            if (! Auth::user()->canManageLivestreams()) {
                Auth::logout();

                return back()->withErrors([
                    'email' => __('Bạn không có quyền truy cập trang quản trị.'),
                ])->withInput($request->only('email'));
            }

            $request->session()->regenerate();

            return redirect()->intended(route(Auth::user()->role === 'admin' ? 'admin.dashboard' : 'admin.livestreams.index'))
                ->with('success', __('Đăng nhập trang quản trị thành công.'));
        }

        return back()->withErrors([
            'email' => __('Email hoặc mật khẩu không đúng.'),
        ])->withInput($request->only('email'));
    }

    // Xử lý đăng xuất Admin
    public function adminLogout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function loginValidationMessages(): array
    {
        return [
            'email.required' => __('Vui lòng nhập địa chỉ email.'),
            'email.string' => __('Vui lòng nhập địa chỉ email hợp lệ.'),
            'email.email' => __('Vui lòng nhập địa chỉ email hợp lệ.'),
            'email.max' => __('Địa chỉ email không được dài quá 255 ký tự.'),
            'password.required' => __('Vui lòng nhập mật khẩu.'),
            'password.string' => __('Vui lòng nhập mật khẩu hợp lệ.'),
        ];
    }
}
