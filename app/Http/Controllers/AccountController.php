<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function edit(Request $request)
    {
        return view('auth.account', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['name' => ['bail', 'required', 'string', 'max:100']], [
            'name.required' => __('Vui lòng nhập tên hiển thị.'),
            'name.string' => __('Tên hiển thị phải là văn bản.'),
            'name.max' => __('Tên hiển thị không được dài quá 100 ký tự.'),
        ]);
        $request->user()->update($data);

        return back()->with('success', __('Đã cập nhật tên hiển thị.'));
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['bail', 'required', 'string', 'current_password'],
            'password' => ['bail', 'required', 'string', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.required' => __('Vui lòng nhập mật khẩu hiện tại.'),
            'current_password.string' => __('Vui lòng nhập mật khẩu hiện tại hợp lệ.'),
            'current_password.current_password' => __('Mật khẩu hiện tại chưa đúng.'),
            'password.required' => __('Vui lòng nhập mật khẩu mới.'),
            'password.string' => __('Vui lòng nhập mật khẩu mới hợp lệ.'),
            'password.confirmed' => __('Mật khẩu nhập lại chưa khớp.'),
            'password.different' => __('Mật khẩu mới phải khác mật khẩu hiện tại.'),
            'password.min' => __('Mật khẩu mới cần có ít nhất 8 ký tự.'),
            'password.password.letters' => __('Mật khẩu mới cần có ít nhất một chữ cái.'),
            'password.password.numbers' => __('Mật khẩu mới cần có ít nhất một chữ số.'),
        ]);
        DB::transaction(function () use ($request, $data) {
            $request->user()->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                    ->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
            }
        });
        $request->session()->regenerate();

        return back()->with('success', __('Đổi mật khẩu thành công.'));
    }
}
