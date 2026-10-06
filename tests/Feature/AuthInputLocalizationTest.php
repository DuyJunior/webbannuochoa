<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthInputLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Mail::fake();
        Notification::fake();
    }

    public function test_malformed_registration_input_is_rejected_before_creating_or_notifying_a_user(): void
    {
        $before = User::count();
        $valid = ['name' => 'Ngọc Linh', 'email' => 'linh@example.test', 'password' => 'secret', 'password_confirmation' => 'secret'];
        foreach ([
            ['password', ['password' => array_fill(0, 6, 'x'), 'password_confirmation' => array_fill(0, 6, 'x')]],
            ['email', ['email' => ['linh@example.test']]],
            ['name', ['name' => ['Ngọc Linh']]],
            ['email', ['email' => str_repeat('a', 256).'@example.test']],
        ] as [$field, $input]) {
            $this->postJson(route('register'), array_replace($valid, $input))
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertSame($before, User::count());
        $this->assertGuest();
        Notification::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_wrong_typed_old_input_does_not_break_registration_or_login_forms(): void
    {
        $this->from(route('register'))->post(route('register'), [
            'name' => ['Ngọc Linh'], 'email' => ['linh@example.test'],
            'password' => 'secret', 'password_confirmation' => 'secret',
        ])->assertRedirect(route('register'))->assertSessionHasErrors(['name', 'email']);
        $this->get(route('register'))->assertOk()->assertSee('Họ và tên phải là văn bản.');

        foreach (['login', 'admin.login'] as $route) {
            $this->from(route($route))->post(route($route), ['email' => ['invalid'], 'password' => ['invalid']])
                ->assertRedirect(route($route))->assertSessionHasErrors(['email', 'password']);
            $this->get(route($route))->assertOk()->assertSee('Vui lòng nhập địa chỉ email hợp lệ.');
        }
        Notification::assertNothingSent();
    }

    public function test_login_rejects_oversized_email_before_attempting_authentication(): void
    {
        foreach (['login', 'admin.login'] as $route) {
            $this->postJson(route($route), ['email' => str_repeat('a', 256).'@example.test', 'password' => 'secret'])
                ->assertUnprocessable()->assertJsonValidationErrors('email')
                ->assertJsonPath('errors.email.0', 'Địa chỉ email không được dài quá 255 ký tự.');
        }
        $this->assertGuest();
    }

    public function test_registration_keeps_the_existing_six_character_password_policy(): void
    {
        $this->post(route('register'), [
            'name' => 'Ngọc Linh', 'email' => 'legacy-policy@example.test',
            'password' => 'abcdef', 'password_confirmation' => 'abcdef',
        ])->assertRedirect(route('verification.notice'));
        $user = User::where('email', 'legacy-policy@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('abcdef', $user->password));
        Notification::assertSentTo($user, \App\Notifications\EmailVerificationCode::class);
        Mail::assertNothingSent();
    }

    public function test_wrong_typed_account_input_does_not_change_profile_or_password(): void
    {
        $user = User::factory()->create(['name' => 'Ngọc Linh', 'password' => 'OldPassword123']);
        $originalHash = $user->password;
        $this->actingAs($user)->from(route('account.edit'))->patch(route('account.update'), ['name' => ['invalid']])
            ->assertRedirect(route('account.edit'))->assertSessionHasErrors('name');
        $this->get(route('account.edit'))->assertOk()->assertSee('Tên hiển thị phải là văn bản.');

        $this->putJson(route('account.password'), [
            'current_password' => ['OldPassword123'], 'password' => 'NewPassword456', 'password_confirmation' => 'NewPassword456',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password')
            ->assertJsonPath('errors.current_password.0', 'Vui lòng nhập mật khẩu hiện tại hợp lệ.');
        $this->putJson(route('account.password'), [
            'current_password' => 'OldPassword123', 'password' => ['NewPassword456'], 'password_confirmation' => ['NewPassword456'],
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertSame('Ngọc Linh', $user->fresh()->name);
        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_account_password_strength_errors_are_localized_and_keep_the_existing_policy(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword123']);
        $this->actingAs($user);
        foreach ([
            ['abcdefg', 'Mật khẩu mới cần có ít nhất 8 ký tự.'],
            ['abcdefgh', 'Mật khẩu mới cần có ít nhất một chữ số.'],
            ['12345678', 'Mật khẩu mới cần có ít nhất một chữ cái.'],
        ] as [$password, $message]) {
            $response = $this->putJson(route('account.password'), [
                'current_password' => 'OldPassword123', 'password' => $password, 'password_confirmation' => $password,
            ])->assertUnprocessable()->assertJsonValidationErrors('password');
            $this->assertContains($message, $response->json('errors.password'));
        }
        $this->assertTrue(Hash::check('OldPassword123', $user->fresh()->password));
    }

    public function test_vietnamese_verification_email_keeps_the_signed_action_and_configured_expiry(): void
    {
        $this->travelTo(now()->startOfSecond());
        config(['auth.verification.expire' => 17]);
        $user = User::factory()->unverified()->create(['name' => "Linh & O'Anh <Bạn>"]);
        $message = (new VerifyEmail)->toMail($user);
        $html = (string) $message->render();
        $text = view('emails.auth.verify-email-text', $message->viewData)->render();
        parse_str(parse_url($message->actionUrl, PHP_URL_QUERY), $query);

        $this->assertSame('Soopi · Xác thực email của bạn', $message->subject);
        $this->assertSame('Xác thực email', $message->actionText);
        $this->assertTrue(URL::hasValidSignature(Request::create($message->actionUrl)));
        $this->assertSame(now()->addMinutes(17)->timestamp, (int) $query['expires']);
        $this->assertStringContainsString(e($message->actionUrl), $html);
        $this->assertStringContainsString('hiệu lực 17 phút', $html);
        $this->assertStringContainsString(e($user->name), $html);
        $this->assertStringNotContainsString($user->name, $html);
        $this->assertStringNotContainsString('Verify Email Address', $html);
        $this->assertStringContainsString($message->actionUrl, $text);
        $this->assertStringContainsString($user->name, $text);

        $this->get($message->actionUrl)->assertRedirect(route('login'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Notification::assertNothingSent();
        Mail::assertNothingSent();
        Http::assertNothingSent();
    }
}
