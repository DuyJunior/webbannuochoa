<?php

namespace Tests\Feature;

use App\Models\Perfume;
use App\Models\User;
use App\Services\FragranceEditorialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_vietnamese_is_default_and_english_can_be_selected_then_switched_back(): void
    {
        $this->get('/login')->assertOk()->assertSee('<html lang="vi"', false)->assertSee('Đăng nhập ngay');
        $this->post('/language', ['locale' => 'en', 'return_to' => '/login?from=store#form'])
            ->assertRedirect('/login?from=store#form')->assertSessionHas('locale', 'en')->assertCookie('soopi_locale', 'en');
        $this->get('/login')->assertOk()->assertSee('<html lang="en"', false)->assertSee('Sign in now');
        $this->post('/language', ['locale' => 'vi'])->assertRedirect('/');
        $this->get('/login')->assertSee('Đăng nhập ngay');
    }

    public function test_cookie_restores_language_and_invalid_cookie_falls_back_to_vietnamese(): void
    {
        $this->withCookie('soopi_locale', 'en')->get('/lien-he')->assertOk()->assertSee('How can we help?');
        $this->withCookie('soopi_locale', '../../invalid')->get('/login')->assertOk()->assertSee('Đăng nhập ngay');
    }

    public function test_switch_rejects_unsupported_languages_and_external_redirects(): void
    {
        $this->postJson('/language', ['locale' => 'fr'])->assertUnprocessable()->assertJsonValidationErrors('locale');
        foreach (['https://example.com', '//example.com', '/\\example.com', '/%2fexample.com', '/%5cexample.com', "/\r\nLocation: evil"] as $target) {
            $this->post('/language', ['locale' => 'en', 'return_to' => $target])->assertRedirect('/');
        }
    }

    public function test_switch_preserves_cart_and_authentication(): void
    {
        $user = User::factory()->create();
        $cart = ['item' => ['quantity' => 2, 'volume_ml' => 200]];
        $this->actingAs($user)->withSession(['cart' => $cart])->post('/language', ['locale' => 'en'])
            ->assertSessionHas('cart', $cart);
        $this->assertAuthenticatedAs($user);
    }

    public function test_switch_keeps_search_terms_with_encoded_spaces_and_vietnamese_characters(): void
    {
        $target = '/?search='.rawurlencode('nước hoa Dior').'&sort=price_asc#san-pham';
        $this->post('/language', ['locale' => 'en', 'return_to' => $target])->assertRedirect($target);
    }

    public function test_english_store_and_admin_render_without_changing_product_data(): void
    {
        $product = Perfume::create(['name' => 'Hương Riêng 200', 'slug' => 'huong-rieng', 'brand' => 'Soopi', 'gender' => 'nu',
            'volume_ml' => 100, 'price' => 1000000, 'weight' => 200, 'stock' => 4, 'is_active' => true]);
        $this->withSession(['locale' => 'en'])->get('/')->assertOk()->assertSee('Explore the collection');
        $this->get('/perfumes/'.$product->id)->assertOk()->assertSee('Choose Size:')->assertSee('Hương Riêng 200');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/products/'.$product->id.'/edit')
            ->assertOk()->assertSee('Add size')->assertSee('Original bottle size (ml)')->assertSee('Hương Riêng 200');
        $this->assertDatabaseHas('perfumes', ['id' => $product->id, 'name' => 'Hương Riêng 200', 'price' => 1000000]);
    }

    public function test_locales_do_not_leak_between_requests(): void
    {
        $this->withSession(['locale' => 'en'])->get('/login')->assertSee('Sign in now');
        $this->withSession(['locale' => 'invalid'])->get('/login')->assertSee('Đăng nhập ngay');
    }

    public function test_english_editorial_and_wishlist_json_keep_product_values_intact(): void
    {
        $product = Perfume::create(['name' => 'Dior Sauvage', 'slug' => 'dior-sauvage', 'brand' => 'Dior', 'gender' => 'nam',
            'concentration' => 'EDP', 'volume_ml' => 100, 'price' => 1000000, 'weight' => 200, 'stock' => 4, 'is_active' => true]);
        $this->withSession(['locale' => 'en'])->getJson('/perfumes/'.$product->id.'/quick-view')
            ->assertOk()->assertJsonPath('name', 'Dior Sauvage')
            ->assertJsonPath('description', 'Bright citrus gives way to spice and vanilla. A contrast between freshness and a warm foundation.');
        $profile = FragranceEditorialService::forPerfume($product);
        $this->assertSame('Citrus · Spice · Vanilla', $profile['family']);
        $this->assertSame(['sage', 'velvet'], $profile['moods']);
        $this->assertSame('Sichuan pepper', $profile['layers']['heart']['notes'][0]);

        $this->actingAs(User::factory()->create())->postJson('/yeu-thich/'.$product->id, ['saved' => true])
            ->assertOk()->assertJsonPath('saved', true)->assertJsonPath('message', __('Đã lưu mùi hương yêu thích.'));
        $this->post('/language', ['locale' => 'vi']);
        $this->get('/perfumes/'.$product->id)->assertSee('Cam chanh · Gia vị · Vanilla');
        $this->assertDatabaseHas('perfumes', ['id' => $product->id, 'stock' => 4, 'price' => 1000000]);
    }

    public function test_english_registration_validation_is_translated(): void
    {
        $this->withSession(['locale' => 'en'])->postJson('/register', ['name' => 'Test', 'email' => '', 'password' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password'])
            ->assertJsonPath('errors.email.0', 'Please enter your email address.');
    }
}
