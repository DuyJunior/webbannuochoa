<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LivestreamStaffMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->canManageLivestreams(), 403);

        return $next($request);
    }
}
