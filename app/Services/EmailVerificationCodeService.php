<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmailVerificationCodeService
{
    public const EXPIRES_MINUTES = 10;

    public const RESEND_SECONDS = 60;

    public const MAX_ATTEMPTS = 5;

    public function send(User $user): string
    {
        if (! app()->runningUnitTests() && ! $this->usesLiveTransport((string) config('mail.default'))) {
            throw new \RuntimeException('Email OTP requires a live mail transport.');
        }

        return DB::transaction(function () use ($user) {
            // Serialize issuance and verification, including simultaneous requests from different sessions.
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($user->hasVerifiedEmail()) {
                return 'verified';
            }
            if ($this->retryAfter($user) > 0) {
                return 'cooldown';
            }

            $previousHash = DB::table('email_verification_codes')->where('user_id', $user->id)->value('code_hash');
            do {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while ($previousHash && Hash::check($code, $previousHash));
            DB::table('email_verification_codes')->updateOrInsert(['user_id' => $user->id], [
                'email' => $user->email,
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
                'sent_at' => now(),
            ]);
            // OTP must leave the request immediately, even when no queue worker is running locally.
            // A transport failure rolls back the code and cooldown so the customer can retry.
            $user->notifyNow((new EmailVerificationCode($code))->locale(app()->getLocale()));

            return 'sent';
        });
    }

    private function usesLiveTransport(string $name, array $seen = []): bool
    {
        if (in_array($name, $seen, true)) {
            return false;
        }
        $mailer = config('mail.mailers.'.$name, []);
        $transport = $mailer['transport'] ?? null;
        if (in_array($transport, ['failover', 'roundrobin'], true)) {
            $children = $mailer['mailers'] ?? [];

            return $children !== [] && collect($children)->every(fn ($child) => $this->usesLiveTransport($child, [...$seen, $name]));
        }

        return in_array($transport, ['smtp', 'sendmail', 'ses', 'ses-v2', 'postmark', 'resend', 'mailgun'], true);
    }

    public function retryAfter(User $user): int
    {
        $sent = DB::table('email_verification_codes')->where('user_id', $user->id)->value('sent_at');

        return $sent ? max(0, Carbon::parse($sent)->addSeconds(self::RESEND_SECONDS)->timestamp - now()->timestamp) : 0;
    }

    public function verify(User $user, #[\SensitiveParameter] string $code): string
    {
        return DB::transaction(function () use ($user, $code) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($user->hasVerifiedEmail()) {
                return 'already_verified';
            }
            $entry = DB::table('email_verification_codes')->where('user_id', $user->id)->first();
            if (! $entry || $entry->email !== $user->email || Carbon::parse($entry->expires_at)->lte(now())) {
                return 'expired';
            }
            if ($entry->attempts >= self::MAX_ATTEMPTS) {
                return 'locked';
            }
            if (! Hash::check($code, $entry->code_hash)) {
                DB::table('email_verification_codes')->where('user_id', $user->id)->increment('attempts');

                return $entry->attempts + 1 >= self::MAX_ATTEMPTS ? 'locked' : 'invalid';
            }

            $user->markEmailAsVerified();
            DB::table('email_verification_codes')->where('user_id', $user->id)->delete();

            return 'verified';
        });
    }
}
