<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LivestreamVisitor
{
    public function hash(Request $request): string
    {
        $key = $request->session()->get('livestream_visitor_key');
        if (!$key) {
            $key = Str::random(32);
            $request->session()->put('livestream_visitor_key', $key);
        }

        return hash_hmac('sha256', $key, config('app.key'));
    }
}
