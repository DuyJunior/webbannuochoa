<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthAndEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('Đăng ký tài khoản');
    }

    public function test_new_users_can_register_and_receive_verification_email(): void
    {
        Notification::fake();

        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'testuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertEquals('user', $user->role);
    }

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertSee('Xác thực Email');
    }

    public function test_email_can_be_verified(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success', 'Xác thực email thành công! Vui lòng quay lại trang đăng nhập để tiếp tục.');
        $this->assertGuest();
        $loginResponse = $this->get(route('login'))->assertOk()->assertSee('Xác thực email thành công!');
        $this->assertSame(1, substr_count($loginResponse->getContent(), 'Xác thực email thành công!'));
    }

    public function test_signed_link_can_verify_email_without_an_active_session(): void
    {
        Event::fake();
        $user = User::factory()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);

        $this->assertGuest();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success', 'Xác thực email thành công! Vui lòng quay lại trang đăng nhập để tiếp tục.');
    }

    public function test_already_verified_link_does_not_send_duplicate_event(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->get($verificationUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Email của bạn đã được xác thực. Vui lòng đăng nhập để tiếp tục.');

        Event::assertNotDispatched(Verified::class);
    }

    public function test_wrong_email_hash_cannot_verify_account(): void
    {
        $user = User::factory()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('other@example.com')]
        );

        $this->get($verificationUrl)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->post(route('verification.send'));

        $response->assertSessionHas('message', 'Đã gửi mã OTP mới. Vui lòng kiểm tra hộp thư và dùng mã mới nhất.');
    }

    public function test_user_can_login_and_is_redirected_to_welcome(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('welcome'));
    }

    public function test_duplicate_registration_explains_how_to_continue_without_deleting_account(): void
    {
        User::factory()->unverified()->create(['email' => 'taken@example.com']);

        $response = $this->post(route('register'), [
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, User::where('email', 'taken@example.com')->count());
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Email này đã được đăng ký.')
            ->assertSee('Đăng nhập để tiếp tục hoặc gửi lại email xác thực');
    }

    public function test_unverified_account_login_opens_resend_verification_page(): void
    {
        $user = User::factory()->unverified()->create([
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect(route('verification.notice'))
            ->assertSessionHas('message', 'Tài khoản của bạn chưa xác thực email. Nhập mã OTP hoặc bấm gửi lại mã bên dưới.');

        $this->assertAuthenticatedAs($user);
        $this->get(route('verification.notice'))->assertOk()->assertSee('Gửi lại mã OTP');
    }

    public function test_admin_is_redirected_to_dashboard(): void
    {
        $admin = User::factory()->create([
            'password' => bcrypt('admin123'),
            'role' => 'admin',
        ]);

        $response = $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'admin123',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard'));
    }
}
