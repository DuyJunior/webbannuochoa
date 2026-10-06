<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailVerificationCode;
use App\Services\EmailVerificationCodeService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class EmailVerificationCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
    }

    private function issue(User $user): EmailVerificationCode
    {
        $user->sendEmailVerificationNotification();

        return Notification::sent($user, EmailVerificationCode::class)->last();
    }

    public function test_registration_sends_a_six_digit_code_and_verifies_only_once(): void
    {
        Event::fake([Verified::class]);
        $this->post('/register', ['name' => 'Linh', 'email' => 'otp@example.test', 'password' => 'secret123', 'password_confirmation' => 'secret123'])
            ->assertRedirect(route('verification.notice'));
        $user = User::where('email', 'otp@example.test')->firstOrFail();
        $notice = Notification::sent($user, EmailVerificationCode::class)->sole();
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $notice->code);
        $this->assertInstanceOf(ShouldBeEncrypted::class, $notice);
        $entry = DB::table('email_verification_codes')->where('user_id', $user->id)->first();
        $this->assertNotSame($notice->code, $entry->code_hash);
        $this->assertTrue(Hash::check($notice->code, $entry->code_hash));
        $this->assertFalse($user->hasVerifiedEmail());
        $this->get(route('verification.notice'))->assertSee('autocomplete="one-time-code"', false)->assertDontSee('nhấn vào liên kết');
        $this->post(route('verification.confirm'), ['code' => $notice->code])->assertRedirect(route('welcome'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('email_verification_codes', ['user_id' => $user->id]);
        $this->post(route('verification.confirm'), ['code' => $notice->code])->assertRedirect(route('welcome'));
        Event::assertDispatchedTimes(Verified::class, 1);
    }

    public function test_wrong_attempts_lock_the_code_even_when_the_next_attempt_is_correct(): void
    {
        $user = User::factory()->unverified()->create();
        $notice = $this->issue($user);
        $wrong = $notice->code === '111111' ? '222222' : '111111';
        $this->actingAs($user)->from(route('verification.notice'));
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('verification.confirm'), ['code' => $wrong])->assertSessionHasErrors('code');
        }
        $this->post(route('verification.confirm'), ['code' => $notice->code])->assertSessionHasErrors(['code' => 'Bạn đã nhập sai 5 lần. Vui lòng yêu cầu mã OTP mới.']);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertSame(5, DB::table('email_verification_codes')->where('user_id', $user->id)->value('attempts'));
    }

    public function test_expiry_is_enforced_at_ten_minutes(): void
    {
        $user = User::factory()->unverified()->create();
        $notice = $this->issue($user);
        $this->travel(10)->minutes();
        $this->actingAs($user)->post(route('verification.confirm'), ['code' => $notice->code])->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertFalse($notice->shouldSend($user, 'mail'));
    }

    public function test_resend_has_account_cooldown_and_replaces_the_previous_code(): void
    {
        $user = User::factory()->unverified()->create();
        $old = $this->issue($user);
        $this->actingAs($user)->post(route('verification.send'))->assertSessionHasErrors('delivery');
        Notification::assertSentToTimes($user, EmailVerificationCode::class, 1);
        $this->travel(61)->seconds();
        $this->post(route('verification.send'))->assertSessionHas('message');
        $latest = Notification::sent($user, EmailVerificationCode::class)->last();
        $this->assertNotSame($old->code, $latest->code);
        $this->assertFalse($old->shouldSend($user, 'mail'));
        $this->assertTrue($latest->shouldSend($user, 'mail'));
        $this->post(route('verification.confirm'), ['code' => $old->code])->assertSessionHasErrors('code');
        $this->post(route('verification.confirm'), ['code' => $latest->code])->assertRedirect(route('welcome'));
    }

    public function test_code_is_bound_to_current_user_and_current_email(): void
    {
        $owner = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();
        $notice = $this->issue($owner);
        $this->actingAs($other)->post(route('verification.confirm'), ['code' => $notice->code, 'user_id' => $owner->id])->assertSessionHasErrors('code');
        $this->assertFalse($owner->fresh()->hasVerifiedEmail());
        $this->assertFalse($other->fresh()->hasVerifiedEmail());
        $owner->update(['email' => 'changed@example.test']);
        $this->actingAs($owner)->post(route('verification.confirm'), ['code' => $notice->code])->assertSessionHasErrors('code');
        $this->assertFalse($notice->shouldSend($owner, 'mail'));
    }

    public function test_guests_and_malformed_codes_cannot_verify_and_secrets_are_not_flashed(): void
    {
        $this->post(route('verification.confirm'), ['code' => '123456'])->assertRedirect(route('login'));
        $this->post(route('verification.send'))->assertRedirect(route('login'));
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->post(route('verification.confirm'), ['code' => ['123456']])->assertSessionHasErrors('code')->assertSessionMissing('_old_input.code');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_mail_and_form_are_localized_without_a_verification_link(): void
    {
        $user = User::factory()->unverified()->create(['name' => 'Linh <Test>']);
        app()->setLocale('en');
        $message = (new EmailVerificationCode('012345'))->toMail($user);
        $html = (string) $message->render();
        $this->assertSame('Soopi · Your email verification code', $message->subject);
        $this->assertNull($message->actionUrl);
        $this->assertStringContainsString('012345', $html);
        $this->assertStringContainsString('10 minutes', $html);
        $this->assertStringContainsString(e($user->name), $html);
        $this->assertStringNotContainsString('/email/verify/', $html);
        $text = view('emails.auth.verification-code-text', $message->viewData)->render();
        $this->assertStringContainsString('012345', $text);
        $response = $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('verification.notice'));
        $response->assertOk()->assertSee('Verify code')->assertSee('Resend code');
        if (getenv('SOOPI_OTP_EXPORT') === '1') {
            $this->withVite();
            foreach (['vi', 'en'] as $locale) {
                file_put_contents(storage_path('app/otp-'.$locale.'.html'), $this->withSession(['locale' => $locale])->get(route('verification.notice'))->getContent());
            }
        }
    }

    public function test_delivery_failure_leaves_registered_account_logged_in_for_retry(): void
    {
        $this->mock(EmailVerificationCodeService::class, fn ($mock) => $mock->shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP unavailable')));
        $this->post('/register', ['name' => 'Linh', 'email' => 'retry@example.test', 'password' => 'secret123', 'password_confirmation' => 'secret123'])
            ->assertRedirect(route('verification.notice'))->assertSessionHasErrors('delivery');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'retry@example.test', 'email_verified_at' => null]);
    }

    public function test_verified_accounts_do_not_receive_another_code(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('verification.notice'))->assertRedirect(route('welcome'));
        $this->post(route('verification.send'))->assertRedirect(route('welcome'));
        Notification::assertNothingSent();
    }

    public function test_encrypted_queue_job_delivers_the_code_through_the_mail_channel(): void
    {
        config(['mail.default' => 'array']);
        $user = User::factory()->unverified()->create();
        $notice = $this->issue($user);
        // The real queue pipeline is exercised inside the test's rolled-back database transaction.
        Notification::swap(new ChannelManager($this->app));
        $user->notify($notice->beforeCommit());
        $payload = DB::table('jobs')->sole()->payload;
        $this->assertStringNotContainsString($notice->code, $payload);
        $this->assertStringNotContainsString('"code"', $payload);
        $job = app('queue')->connection('database')->pop('default');
        $job->fire();
        $messages = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $mail = $messages->first()->getOriginalMessage();
        $this->assertSame($user->email, $mail->getTo()[0]->getAddress());
        $this->assertStringContainsString($notice->code, $mail->getTextBody());
        $this->assertStringContainsString($notice->code, $mail->getHtmlBody());
        $this->assertStringNotContainsString('/email/verify/', $mail->getHtmlBody());
    }

    public function test_new_codes_are_sent_immediately_without_a_queue_worker(): void
    {
        config(['mail.default' => 'array', 'queue.default' => 'database']);
        Notification::swap(new ChannelManager($this->app));
        $user = User::factory()->unverified()->create();
        $this->assertSame('sent', app(EmailVerificationCodeService::class)->send($user));
        $this->assertDatabaseCount('jobs', 0);
        $messages = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $mail = $messages->first()->getOriginalMessage();
        $this->assertSame($user->email, $mail->getTo()[0]->getAddress());
        preg_match('/\b([0-9]{6})\b/', $mail->getTextBody(), $matches);
        $hash = DB::table('email_verification_codes')->where('user_id', $user->id)->value('code_hash');
        $this->assertTrue(Hash::check($matches[1], $hash));
    }

    public function test_failed_delivery_does_not_replace_the_previous_code_or_restart_cooldown(): void
    {
        $user = User::factory()->unverified()->create();
        $previous = $this->issue($user);
        $this->travel(61)->seconds();
        Notification::shouldReceive('sendNow')->once()->andThrow(new RuntimeException('SMTP unavailable'));
        $this->actingAs($user)->from(route('verification.notice'))->post(route('verification.send'))->assertSessionHasErrors('delivery');
        $this->assertTrue(Hash::check($previous->code, DB::table('email_verification_codes')->where('user_id', $user->id)->value('code_hash')));
        $this->assertSame(0, app(EmailVerificationCodeService::class)->retryAfter($user));
        $this->get(route('verification.notice'))->assertOk()->assertSee('Chưa thể gửi mã OTP.')->assertSee('data-retry-after="0"', false);
    }

    public function test_log_only_mail_configuration_cannot_claim_otp_delivery(): void
    {
        config(['mail.default' => 'log']);
        $user = User::factory()->unverified()->create();
        $this->app->instance('env', 'local');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Email OTP requires a live mail transport.');
        app(EmailVerificationCodeService::class)->send($user);
    }
}
