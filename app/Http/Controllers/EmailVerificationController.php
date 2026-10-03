<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
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
