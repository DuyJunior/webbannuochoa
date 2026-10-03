<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Recover a stale cache mutex promptly after a container restart.
// Per-order transaction locks and idempotent inventory release protect data.
Schedule::command('orders:expire-unpaid')->everyMinute()->withoutOverlapping(5);
Schedule::command('orders:dispatch-emails')->everyMinute()->withoutOverlapping(5);

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
