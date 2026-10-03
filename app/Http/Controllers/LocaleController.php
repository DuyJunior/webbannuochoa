<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'in:vi,en'],
            'return_to' => ['nullable', 'string', 'max:4096'],
        ]);
        $target = $data['return_to'] ?? '/';
        // Only a local path may be used, including its filters and section anchor.
        if (! str_starts_with($target, '/') || str_starts_with($target, '//')
            || preg_match('/[\\\\\x00-\x1f\x7f]/', rawurldecode($target))
            || str_starts_with(rawurldecode($target), '//')) {
            $target = '/';
        }
        $request->session()->put('locale', $data['locale']);

        return redirect($target)->withCookie(cookie('soopi_locale', $data['locale'], 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
    }
}
