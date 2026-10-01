<?php

namespace Tests\Feature;

use App\Models\Perfume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScentGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function product(string $slug, array $attributes = []): Perfume
    {
        return Perfume::create(array_merge([
            'slug' => $slug, 'name' => $slug, 'brand' => 'Soopi', 'gender' => 'nu',
            'volume_ml' => 100, 'price' => 1500000, 'stock' => 4, 'is_active' => true,
        ], $attributes));
    }

    public function test_gallery_uses_live_prices_and_each_active_product_appears_once(): void
    {
        $feature = $this->product('miss-dior-blooming-bouquet', ['sale_price' => 1234567]);
        foreach (config('scent-gallery.selection') as $slug) $this->product($slug);
        $extra = $this->product('another-scent');
        $hidden = $this->product('inactive-scent', ['is_active' => false]);
        $response = $this->get('/')->assertOk()->assertSee('1.234.567₫')
            ->assertViewHas('galleryFeatured', fn ($p) => $p->id === $feature->id)
            ->assertViewHas('gallerySelection', fn ($items) => $items->count() === 4)
            ->assertViewHas('galleryRemaining', fn ($items) => $items->modelKeys() === [$extra->id])
            ->assertDontSee('data-product-id="'.$hidden->id.'"', false);
        foreach (Perfume::where('is_active', true)->get() as $product) {
            $this->assertSame(1, substr_count($response->getContent(), 'data-product-id="'.$product->id.'"'));
        }
    }

    public function test_filtering_and_sorting_never_insert_unrelated_featured_products(): void
    {
        $this->product('miss-dior-blooming-bouquet', ['price' => 2500000, 'gender' => 'nu']);
        $expensive = $this->product('men-expensive', ['price' => 2000000, 'gender' => 'nam']);
        $cheap = $this->product('men-cheap', ['price' => 1000000, 'gender' => 'nam']);
        $this->get('/?gender=nam&sort=price_asc')->assertOk()
            ->assertViewHas('galleryFeatured', null)
            ->assertViewHas('perfumes', fn ($items) => $items->modelKeys() === [$cheap->id, $expensive->id])
            ->assertDontSee('scent-exhibition', false)->assertDontSee('miss-dior-blooming-bouquet');
    }

    public function test_single_product_and_empty_catalog_have_valid_fallbacks(): void
    {
        $this->get('/')->assertOk()->assertSee('Chưa tìm thấy mùi hương phù hợp');
        $only = $this->product('one-scent', ['image_url' => 'images/perfume-default.jpg']);
        $this->get('/')->assertOk()->assertSee('scent-exhibition-solo', false)
            ->assertSee('images/perfume-default.jpg', false)
            ->assertViewHas('galleryFeatured', fn ($p) => $p->id === $only->id);
        $this->get('/?search=missing')->assertOk()->assertSee('Chưa tìm thấy mùi hương phù hợp')
            ->assertDontSee('data-product-id="'.$only->id.'"', false);
    }
}
