<?php

namespace App\Jobs;

use App\Services\OrderEmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOrderStatusEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 35;

    public function __construct(public int $emailId)
    {
        // Never inherit a sync queue: checkout must not wait on or fail because of SMTP.
        $this->onConnection('database')->onQueue('default');
    }

    public function handle(OrderEmailService $emails): void
    {
        $emails->deliver($this->emailId);
    }
}
