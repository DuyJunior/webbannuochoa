<?php

namespace App\Support;

final class DemoMode
{
    public static function enabled(): bool
    {
        return app()->environment(['local', 'testing']) && (bool) config('demo.enabled');
    }
}
