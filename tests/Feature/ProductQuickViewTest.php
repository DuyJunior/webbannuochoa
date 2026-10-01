<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Perfume;
use App\Models\User;
use App\Services\CartQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductQuickViewTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $changes = []): Perfume
    {
        return Perfume::create(array_merge([
            'category_id' => Category::create(['name' => 'Hương hoa'])->id,
            'name' => 'Miss Dior Blooming Bouquet', 'slug' => 'miss-dior-blooming-bouquet',
            'brand' => 'Dior', 'gender' => 'nu', 'concentration' => 'EDT',
            'volume_ml' => 75, 'price' => 3850000, 'sale_price' => 3390000,
            'stock' => 8, 'stock_10ml' => 2, 'stock_50ml' => 0,
            'description' => '<p>Hương hoa hồng dịu dàng, thanh lịch cho mỗi ngày.</p>',
            'is_active' => true,
        ], $changes));
    }

    public function test_guest_gets_real_variant_quotes_and_stock_without_creating_a_login_intent(): void
    {
        $product = $this->product();
        $response = $this->getJson(route('perfumes.quick-view', $product))->assertOk()
            ->assertJsonPath('authenticated', false)
            ->assertJsonPath('name', $product->name)
            ->assertJsonPath('description', fn ($text) => str_contains($text, 'Mẫu đơn') && ! str_contains($text, 'thanh lịch cho mỗi ngày'))
            ->assertJsonPath('variants.0.volume', 75)
            ->assertJsonPath('variants.0.price', 3390000)
            ->assertJsonPath('variants.0.original_price', 3850000)
            ->assertJsonPath('variants.0.stock', 8)
            ->assertJsonPath('variants.1.volume', 50)
            ->assertJsonPath('variants.1.stock', 0)
            ->assertJsonPath('variants.1.max_quantity', 0)
            ->assertJsonPath('variants.2.volume', 10)
            ->assertJsonPath('variants.2.price', app(CartQuoteService::class)->unitPrice($product, 10))
            ->assertSessionMissing('url.intended');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        if (getenv('SOOPI_EXPORT_QUICK_VIEW') === '1') {
            $directory = storage_path('app/backups/quick-view-review');
            File::ensureDirectoryExists($directory);
            File::put($directory.'/guest.json', $response->getContent());
            $this->actingAs(User::factory()->create());
            File::put($directory.'/member.json', $this->getJson(route('perfumes.quick-view', $product))->assertOk()->getContent());
            $product->update(['stock' => 0, 'stock_10ml' => 0]);
            File::put($directory.'/sold-out.json', $this->getJson(route('perfumes.quick-view', $product))->assertOk()->getContent());
        }
    }

    public function test_unverified_variants_do_not_reuse_seeded_scent_claims(): void
    {
        $product = $this->product(['concentration' => 'EDP', 'description' => 'Unverified vanilla claim']);
        $this->getJson(route('perfumes.quick-view', $product))->assertOk()
            ->assertJsonPath('description', '');
    }

    public function test_inactive_deleted_and_missing_products_are_unavailable(): void
    {
        $product = $this->product(['is_active' => false]);
        $this->getJson(route('perfumes.quick-view', $product))->assertNotFound();
        $this->get(route('perfumes.quick-view.login', $product))->assertNotFound();
        $product->update(['is_active' => true]);
        $product->delete();
        $this->getJson(route('perfumes.quick-view', $product->id))->assertNotFound();
        $this->get(route('perfumes.quick-view.login', $product->id))->assertNotFound();
        $this->getJson(route('perfumes.quick-view', 99999))->assertNotFound();
    }

    public function test_login_cta_returns_the_customer_to_the_selected_product(): void
    {
        $product = $this->product();
        $this->get(route('perfumes.quick-view.login', $product).'?redirect=https://example.test')
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', route('perfumes.show', $product));
        $this->actingAs(User::factory()->create())
            ->get(route('perfumes.quick-view.login', $product))
            ->assertRedirect(route('perfumes.show', $product));
    }

    public function test_remaining_quantity_matches_unpersonalised_cart_lines(): void
    {
        $product = $this->product();
        $this->actingAs(User::factory()->create())->withSession(['cart' => [
            $product->id => 3,
            'item_'.$product->id.'_10' => ['perfume_id' => $product->id, 'volume_ml' => 10, 'quantity' => 2],
        ]])->getJson(route('perfumes.quick-view', $product))->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('cart_url', route('cart.add', $product))
            ->assertJsonPath('variants.0.in_cart', 3)
            ->assertJsonPath('variants.0.max_quantity', 5)
            ->assertJsonPath('variants.2.in_cart', 2)
            ->assertJsonPath('variants.2.max_quantity', 0);
    }

    public function test_default_volume_is_not_duplicated_and_out_of_stock_variants_remain_visible(): void
    {
        $product = $this->product(['volume_ml' => 50, 'stock' => 0, 'stock_50ml' => 100, 'stock_10ml' => 0]);
        $this->getJson(route('perfumes.quick-view', $product))->assertOk()
            ->assertJsonCount(2, 'variants')
            ->assertJsonPath('variants.0.volume', 50)
            ->assertJsonPath('variants.0.stock', 0)
            ->assertJsonPath('variants.0.max_quantity', 0)
            ->assertJsonPath('variants.1.max_quantity', 0);
    }

    public function test_existing_cart_endpoint_reprices_the_selected_variant_and_rejects_tampering(): void
    {
        $product = $this->product();
        $this->actingAs(User::factory()->create())
            ->from(route('home'))->post(route('cart.add', $product), [
                'quantity' => 1, 'volume_ml' => 10, 'unit_price' => 1,
            ])->assertRedirect(route('home'))->assertSessionHas('cart.item_'.$product->id.'_10.unit_price', app(CartQuoteService::class)->unitPrice($product, 10));
        $this->from(route('home'))->post(route('cart.add', $product), [
            'quantity' => 3, 'volume_ml' => 10,
        ])->assertSessionHasErrors('quantity');
        $this->from(route('home'))->post(route('cart.add', $product), [
            'quantity' => 1, 'volume_ml' => 999,
        ])->assertSessionHasErrors('cart');
    }
}
