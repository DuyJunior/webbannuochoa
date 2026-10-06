<?php

namespace App\Notifications;

use App\Services\EmailVerificationCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmailVerificationCode extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 35;

    public function __construct(#[\SensitiveParameter] public string $code)
    {
        $this->onConnection('database')->onQueue('default')->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function backoff(): array
    {
        return [30, 60];
    }

    public function shouldSend($notifiable, string $channel): bool
    {
        $entry = DB::table('email_verification_codes')->where('user_id', $notifiable->id)->first();

        return $channel === 'mail' && ! $notifiable->hasVerifiedEmail() && $entry
            && $entry->email === $notifiable->email && Carbon::parse($entry->expires_at)->isFuture()
            && $entry->attempts < EmailVerificationCodeService::MAX_ATTEMPTS
            && Hash::check($this->code, $entry->code_hash);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject(__('Soopi · Mã xác thực email của bạn'))
            ->view(['html' => 'emails.auth.verification-code', 'text' => 'emails.auth.verification-code-text'], [
                'customerName' => $notifiable->name,
                'code' => $this->code,
                'expiresMinutes' => EmailVerificationCodeService::EXPIRES_MINUTES,
            ]);
    }
}
