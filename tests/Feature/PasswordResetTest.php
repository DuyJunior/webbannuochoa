<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use DatabaseMigrations;

    private ChannelManager $realNotifications;

    private QueueManager $realQueue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Mail::fake();
        $this->realNotifications = Notification::getFacadeRoot();
        $this->realQueue = Queue::getFacadeRoot();
        Notification::fake();
        Queue::fake();
        config([
            'auth.defaults.passwords' => 'users', 'auth.passwords.users.expire' => 60,
            'auth.passwords.users.throttle' => 60, 'auth.timebox_duration' => 0,
            'mail.default' => 'smtp', 'mail.mailers.smtp.transport' => 'smtp',
            'queue.default' => 'sync', 'queue.connections.database.connection' => 'sqlite',
            'queue.connections.database.table' => 'jobs',
            'app.url' => 'https://shop.example.test',
        ]);
    }

    private function requestedToken(User $user): string
    {
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))->assertSessionHas('status')->assertSessionHasNoErrors();

        return Notification::sent($user, ResetPasswordNotification::class)->last()->token;
    }

    private function resetData(User $user, string $token, string $password = 'NewPassword123'): array
    {
        return ['email' => $user->email, 'token' => $token, 'password' => $password, 'password_confirmation' => $password];
    }

    public function test_recovery_pages_and_login_links_are_accessible_without_authentication(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Quên mật khẩu?')
            ->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get(route('login'))->assertOk()->assertSee(route('password.request'), false);
        $this->get(route('admin.login'))->assertOk()->assertSee(route('password.request'), false);
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()->assertSee('name="password_confirmation"', false)
            ->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_known_unknown_and_broker_throttled_emails_receive_the_same_neutral_response(): void
    {
        $user = User::factory()->create();
        $token = $this->requestedToken($user);
        $message = session('status');
        foreach (['missing@example.test', $user->email] as $email) {
            $this->post(route('password.email'), ['email' => $email])
                ->assertRedirect(route('password.request'))->assertSessionHas('status', $message)->assertSessionHasNoErrors();
        }
        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
        Notification::assertCount(1);
        $stored = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
        $this->assertNotSame($token, $stored);
        $this->assertTrue(Hash::check($token, $stored));
        $this->assertDatabaseCount('password_reset_tokens', 1);
        $this->assertGuest();
    }

    public function test_a_new_request_after_broker_throttle_replaces_the_previous_link(): void
    {
        $user = User::factory()->create();
        $oldToken = $this->requestedToken($user);
        $this->travel(61)->seconds();
        $newToken = $this->requestedToken($user);
        $this->assertNotSame($oldToken, $newToken);
        $this->assertFalse(Password::tokenExists($user, $oldToken));
        $this->assertTrue(Password::tokenExists($user, $newToken));
    }

    public function test_valid_reset_changes_password_consumes_token_and_preserves_role_and_verification(): void
    {
        Event::fake([PasswordReset::class]);
        $user = User::factory()->unverified()->create(['role' => 'admin', 'password' => 'OldPassword123', 'remember_token' => 'old-remember-token']);
        $token = $this->requestedToken($user);
        $this->post(route('password.update'), $this->resetData($user, $token))
            ->assertRedirect(route('login'))->assertSessionHas('success')->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $user->password));
        $this->assertFalse(Hash::check('OldPassword123', $user->password));
        $this->assertNotSame('old-remember-token', $user->remember_token);
        $this->assertSame('admin', $user->role);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertGuest();
        Event::assertDispatchedTimes(PasswordReset::class, 1);
        $this->post(route('password.update'), $this->resetData($user, $token, 'AnotherPassword456'))
            ->assertRedirect(route('password.request'))->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
        Event::assertDispatchedTimes(PasswordReset::class, 1);
    }

    public function test_invalid_expired_and_unknown_links_have_the_same_error_and_do_not_change_password(): void
    {
        $user = User::factory()->create(['password' => 'OriginalPassword123']);
        $token = $this->requestedToken($user);
        $this->post(route('password.update'), $this->resetData($user, str_repeat('a', 64)))
            ->assertRedirect(route('password.request'))->assertSessionHasErrors('email');
        $message = session('errors')->first('email');
        $this->post(route('password.update'), [...$this->resetData($user, $token), 'email' => 'unknown@example.test'])
            ->assertRedirect(route('password.request'))->assertSessionHasErrors(['email' => $message]);
        $other = User::factory()->create(['password' => 'OtherPassword123']);
        $this->post(route('password.update'), $this->resetData($other, $token))
            ->assertRedirect(route('password.request'))->assertSessionHasErrors(['email' => $message]);
        $this->assertTrue(Hash::check('OtherPassword123', $other->fresh()->password));
        $this->travel(61)->minutes();
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()->assertSee($message)->assertDontSee('name="password_confirmation"', false);
        $this->post(route('password.update'), $this->resetData($user, $token))
            ->assertRedirect(route('password.request'))->assertSessionHasErrors(['email' => $message]);
        $this->assertTrue(Hash::check('OriginalPassword123', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_password_strength_and_confirmation_errors_do_not_flash_secrets_or_consume_token(): void
    {
        $user = User::factory()->create();
        $token = $this->requestedToken($user);
        foreach (['short1', 'onlyletters', '123456789', 'ValidPassword123'] as $password) {
            $data = $this->resetData($user, $token, $password);
            if ($password === 'ValidPassword123') {
                $data['password_confirmation'] = 'DoesNotMatch456';
            }
            $this->post(route('password.update'), $data)->assertSessionHasErrors('password')
                ->assertRedirect(route('password.reset', ['token' => $token, 'email' => $user->email]));
            $this->assertSame(['email' => $user->email], session('_old_input'));
            $this->assertTrue(Password::tokenExists($user, $token));
        }
    }

    public function test_password_reset_revokes_only_the_target_users_database_sessions(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach (['target-browser' => $user, 'target-phone' => $user, 'other-browser' => $other] as $id => $owner) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $owner->id, 'payload' => base64_encode(serialize([])), 'last_activity' => now()->timestamp]);
        }
        $token = $this->requestedToken($user);
        $this->post(route('password.update'), $this->resetData($user, $token))->assertRedirect(route('login'));
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'other-browser', 'user_id' => $other->id]);
        $this->assertGuest();
    }

    public function test_recovery_uses_an_encrypted_database_job_even_when_default_queue_is_sync(): void
    {
        Notification::swap($this->realNotifications);
        Queue::swap($this->realQueue);
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect(route('password.request'));
        $this->assertDatabaseCount('jobs', 1);
        $row = DB::table('jobs')->first();
        $payload = json_decode($row->payload, true, flags: JSON_THROW_ON_ERROR);
        $job = unserialize(Crypt::decrypt($payload['data']['command']));
        $this->assertInstanceOf(SendQueuedNotifications::class, $job);
        $this->assertInstanceOf(ResetPasswordNotification::class, $job->notification);
        $this->assertTrue($job->shouldBeEncrypted);
        $this->assertSame('default', $row->queue);
        $this->assertSame('database', $job->connection);
        $this->assertStringNotContainsString($job->notification->token, $row->payload);
        $this->assertTrue(Password::tokenExists($user, $job->notification->token));
        Mail::assertNothingSent();
    }

    public function test_queue_failure_still_returns_neutral_response_without_logging_a_token(): void
    {
        Notification::swap($this->realNotifications);
        Queue::swap($this->realQueue);
        Log::spy();
        config(['queue.connections.database.table' => 'missing_recovery_queue']);
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))->assertSessionHas('status')->assertSessionHasNoErrors();
        Log::shouldHaveReceived('warning')->once()->with('Password recovery could not be queued.', [
            'exception_class' => \Illuminate\Database\QueryException::class,
        ]);
        Mail::assertNothingSent();
    }

    public function test_worker_suppresses_expired_consumed_replaced_and_log_transport_tokens(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $notification = new ResetPasswordNotification($token);
        $this->assertTrue($notification->shouldSend($user, 'mail'));
        foreach (['log', 'array', 'failover'] as $mailer) {
            config(['mail.default' => $mailer, 'mail.mailers.failover' => ['transport' => 'failover', 'mailers' => ['smtp', 'log']]]);
            $this->assertFalse($notification->shouldSend($user, 'mail'));
        }
        config(['mail.default' => 'smtp']);
        $this->travel(61)->minutes();
        $this->assertFalse($notification->shouldSend($user, 'mail'));
        $replacement = Password::createToken($user);
        $this->assertFalse($notification->shouldSend($user, 'mail'));
        $current = new ResetPasswordNotification($replacement);
        $this->assertTrue($current->shouldSend($user, 'mail'));
        Password::deleteToken($user);
        $this->assertFalse($current->shouldSend($user, 'mail'));
    }

    public function test_branded_mail_uses_trusted_app_url_and_escapes_html_but_keeps_plain_text_literal(): void
    {
        $user = User::factory()->create(['name' => "An & O'Neil <người nhận>"]);
        $notification = new ResetPasswordNotification(str_repeat('b', 64));
        $this->withServerVariables(['HTTP_HOST' => 'untrusted.example.test'])->get('/quen-mat-khau')->assertOk();
        $mail = $notification->toMail($user);
        $this->assertSame('Soopi · Đặt lại mật khẩu', $mail->subject);
        $this->assertStringStartsWith('https://shop.example.test/dat-lai-mat-khau/', $mail->viewData['resetUrl']);
        $this->assertStringNotContainsString('untrusted.example.test', $mail->viewData['resetUrl']);
        $this->assertSame(60, $mail->viewData['expiresMinutes']);
        $html = view('emails.auth.reset-password', $mail->viewData)->render();
        $text = view('emails.auth.reset-password-text', $mail->viewData)->render();
        $this->assertStringContainsString('https://shop.example.test/images/brand/soopi-petal-logo.png', $html);
        $this->assertStringNotContainsString('untrusted.example.test/images/brand/', $html);
        $this->assertStringContainsString(e($user->name), $html);
        $this->assertStringNotContainsString($user->name, $html);
        $this->assertStringContainsString($user->name, $text);
        $this->assertStringContainsString($mail->viewData['resetUrl'], $text);
    }

    public function test_request_and_reset_posts_are_rate_limited(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('password.email'), ['email' => 'missing@example.test'])->assertRedirect();
        }
        $this->post(route('password.email'), ['email' => 'missing@example.test'])->assertStatus(429);
        foreach (range(1, 10) as $attempt) {
            $this->post(route('password.update'), [])->assertRedirect();
        }
        $this->post(route('password.update'), [])->assertStatus(429);
        Notification::assertNothingSent();
    }
}
