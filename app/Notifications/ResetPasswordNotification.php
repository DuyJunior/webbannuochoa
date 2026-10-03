<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Password;

class ResetPasswordNotification extends ResetPassword implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 35;

    public function __construct(#[\SensitiveParameter] string $token)
    {
        parent::__construct($token);
        // Never wait for SMTP in the recovery request, even with QUEUE_CONNECTION=sync.
        $this->onConnection('database')->onQueue('default')->afterCommit();
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function shouldSend($notifiable, string $channel): bool
    {
        // Do not send expired/replaced/consumed tokens or expose them through a log transport.
        return $channel === 'mail' && $this->liveMailer((string) config('mail.default'))
            && Password::tokenExists($notifiable, $this->token);
    }

    public function toMail($notifiable): MailMessage
    {
        $broker = config('auth.defaults.passwords');
        $resetUrl = rtrim((string) config('app.url'), '/').route('password.reset', [
            'token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset(),
        ], false);

        return (new MailMessage)->subject('Soopi · Đặt lại mật khẩu')
            ->view(['html' => 'emails.auth.reset-password', 'text' => 'emails.auth.reset-password-text'], [
                'customerName' => $notifiable->name,
                'resetUrl' => $resetUrl,
                'expiresMinutes' => (int) config('auth.passwords.'.$broker.'.expire', 60),
            ]);
    }

    private function liveMailer(string $name, array $seen = []): bool
    {
        if (in_array($name, $seen, true)) {
            return false;
        }
        $mailer = config('mail.mailers.'.$name, []);
        $transport = $mailer['transport'] ?? null;
        if (in_array($transport, ['failover', 'roundrobin'], true)) {
            $children = $mailer['mailers'] ?? [];

            return $children !== [] && collect($children)->every(fn ($child) => $this->liveMailer($child, [...$seen, $name]));
        }

        return in_array($transport, ['smtp', 'sendmail', 'ses', 'ses-v2', 'postmark', 'resend', 'mailgun'], true);
    }
}
