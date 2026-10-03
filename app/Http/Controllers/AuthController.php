<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // Hiển thị form đăng ký
    public function showRegistrationForm()
    {
        if (Auth::check()) {
            if (Auth::user()->canManageLivestreams()) {
                return redirect()->route(Auth::user()->role === 'admin' ? 'admin.dashboard' : 'admin.livestreams.index');
            }
            if (!Auth::user()->hasVerifiedEmail()) {
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
            'name'     => 'bail|required|string|max:255',
            'email'    => 'bail|required|string|max:255|email|unique:users,email',
            'password' => 'bail|required|string|confirmed|min:6',
        ], [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'name.string' => 'Họ và tên phải là văn bản.',
            'name.max' => 'Họ và tên không được dài quá 255 ký tự.',
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.string' => 'Vui lòng nhập địa chỉ email hợp lệ.',
            'email.max' => 'Địa chỉ email không được dài quá 255 ký tự.',
            'email.unique' => 'Email này đã được đăng ký. Vui lòng đăng nhập; nếu chưa xác thực, bạn có thể gửi lại liên kết xác thực.',
            'email.email' => 'Vui lòng nhập địa chỉ email hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.string' => 'Vui lòng nhập mật khẩu hợp lệ.',
            'password.confirmed' => 'Mật khẩu nhập lại chưa khớp.',
            'password.min' => 'Mật khẩu cần có ít nhất 6 ký tự.',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
            'role'     => 'user', // Mặc định là user
        ]);

        // Gửi email xác thực
        $user->sendEmailVerificationNotification();

        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('success', 'Vui lòng kiểm tra email để xác thực tài khoản.');
    }

    // Hiển thị form đăng nhập
    public function showLoginForm()
    {
        if (Auth::check()) {
            if (Auth::user()->canManageLivestreams()) {
                return redirect()->route(Auth::user()->role === 'admin' ? 'admin.dashboard' : 'admin.livestreams.index');
            }
            if (!Auth::user()->hasVerifiedEmail()) {
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
            'email'    => 'bail|required|string|max:255|email',
            'password' => 'bail|required|string',
        ], $this->loginValidationMessages());

        if (Auth::attempt($request->only('email', 'password'))) {
            $request->session()->regenerate();

            if (Auth::user()->canManageLivestreams()) {
                return redirect()->intended(route(Auth::user()->role === 'admin' ? 'admin.dashboard' : 'admin.livestreams.index'))
                    ->with('success', 'Đăng nhập thành công.');
            }

            if (!Auth::user()->hasVerifiedEmail()) {
                return redirect()->route('verification.notice')
                    ->with('message', 'Tài khoản của bạn chưa xác thực email. Vui lòng kiểm tra hộp thư hoặc bấm gửi lại liên kết bên dưới.');
            }

            return redirect()->intended(route('welcome'))
                ->with('success', 'Đăng nhập thành công! Chào mừng ' . Auth::user()->name . ' quay trở lại.');
        }

        return back()->withErrors([
            'email' => 'Email hoặc mật khẩu không đúng.',
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
            return redirect()->route('home')->with('error', 'Bạn không có quyền truy cập vào trang quản trị viên!');
        }

        return view('admin.login');
    }

    // Xử lý đăng nhập Admin
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email'    => 'bail|required|string|max:255|email',
            'password' => 'bail|required|string',
        ], $this->loginValidationMessages());

        if (Auth::attempt($request->only('email', 'password'))) {
            if (!Auth::user()->canManageLivestreams()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Bạn không có quyền truy cập trang quản trị.',
                ])->withInput($request->only('email'));
            }

            $request->session()->regenerate();
            return redirect()->intended(route(Auth::user()->role === 'admin' ? 'admin.dashboard' : 'admin.livestreams.index'))
                ->with('success', 'Đăng nhập trang quản trị thành công.');
        }

        return back()->withErrors([
            'email' => 'Email hoặc mật khẩu không đúng.',
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
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.string' => 'Vui lòng nhập địa chỉ email hợp lệ.',
            'email.email' => 'Vui lòng nhập địa chỉ email hợp lệ.',
            'email.max' => 'Địa chỉ email không được dài quá 255 ký tự.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.string' => 'Vui lòng nhập mật khẩu hợp lệ.',
        ];
    }
}
