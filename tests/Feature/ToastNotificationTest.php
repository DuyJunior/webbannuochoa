<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToastNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (!getenv('SOOPI_TOAST_EXPORT')) $this->withoutVite();
    }

    public function test_store_flash_types_render_once_and_are_escaped(): void
    {
        $messages = ['success' => 'Toast saved', 'error' => '<script>bad()</script>', 'warning' => 'Shipping unavailable',
            'info' => 'Information', 'status' => 'Reset requested', 'message' => 'OTP sent'];
        $response = $this->withSession($messages)->get(route('login'))->assertOk();
        $response->assertSee('&lt;script&gt;bad()&lt;/script&gt;', false)->assertDontSee('<script>bad()</script>', false);
        foreach ($messages as $message) $this->assertSame(1, substr_count($response->getContent(), e($message)));
        $this->assertSame(1, substr_count($response->getContent(), 'js/soopi-toast.js'));
        $response->assertDontSee('public-flash')->assertDontSee('luxury-auth-alert alert-success');
    }

    public function test_admin_and_store_notifications_are_bilingual(): void
    {
        foreach (['vi' => 'Đóng thông báo', 'en' => 'Dismiss notification'] as $locale => $label) {
            $response = $this->withSession(['locale' => $locale, 'success' => 'Toast preview'])
                ->get(route('login'))->assertOk()->assertSee('data-close="'.$label.'"', false);
            if (getenv('SOOPI_TOAST_EXPORT')) file_put_contents(storage_path('app/toast-store-'.$locale.'.html'), $response->getContent());
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['vi', 'en'] as $locale) {
            $response = $this->withSession(['locale' => $locale, 'success' => 'Toast admin preview'])
                ->get(route('admin.products.index'))->assertOk()->assertSee('data-toast-source="success"', false);
            $this->assertSame(1, substr_count($response->getContent(), 'Toast admin preview'));
            if (getenv('SOOPI_TOAST_EXPORT')) file_put_contents(storage_path('app/toast-admin-'.$locale.'.html'), $response->getContent());
        }
    }

    public function test_validation_keeps_field_feedback_and_provides_an_error_toast(): void
    {
        $this->post(route('login'), ['email' => 'bad', 'password' => ''])->assertSessionHasErrors(['email', 'password']);
        $this->get(route('login'))->assertOk()->assertSee('data-toast-source="error"', false)
            ->assertSee('luxury-auth-alert alert-danger', false);
    }

    public function test_admin_login_includes_notifications_without_duplicate_flash(): void
    {
        $response = $this->withSession(['error' => 'Admin login failed'])->get(route('admin.login'))->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'Admin login failed'));
        $response->assertSee('js/soopi-toast.js');
    }
}
