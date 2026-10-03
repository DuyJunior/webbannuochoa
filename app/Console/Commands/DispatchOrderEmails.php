<?php

namespace App\Console\Commands;

use App\Services\OrderEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DispatchOrderEmails extends Command
{
    protected $signature = 'orders:dispatch-emails {--check : Check schema and delivery readiness without queueing or sending}';

    protected $description = 'Recover pending transactional order emails into the database queue';

    public function handle(OrderEmailService $emails): int
    {
        $queueSchema = Schema::connection(config('queue.connections.database.connection') ?: config('database.default'));
        if (! Schema::hasTable('order_emails') || ! $queueSchema->hasTable(config('queue.connections.database.table', 'jobs'))) {
            $this->error('Order email tables are missing. Run php artisan migrate --force before serving this release.');

            return self::FAILURE;
        }
        if ($this->option('check')) {
            $this->info('Order email outbox and database queue tables are ready.');
            if (! config('order_emails.enabled', true) || ! $emails->realMailer($emails->mailer())) {
                $this->warn('Delivery is paused: configure a real mail transport and enable ORDER_EMAILS_ENABLED. Outbox entries are retained.');
            }

            return self::SUCCESS;
        }
        $this->info('Queued '.$emails->enqueuePending().' order email(s).');

        return self::SUCCESS;
    }
}
