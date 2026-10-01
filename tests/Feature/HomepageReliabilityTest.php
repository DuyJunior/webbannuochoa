<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Perfume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function perfume(array $attributes = []): Perfume
    {
        return Perfume::create(array_merge([
            'name' => 'Dior thử nghiệm', 'slug' => fake()->unique()->slug(),
            'brand' => 'Dior', 'gender' => 'nu', 'concentration' => 'EDP',
            'volume_ml' => 100, 'price' => 1200000, 'stock' => 3,
            'description' => 'Hương hoa hồng', 'is_active' => true,
        ], $attributes));
    }

    public function test_filter_form_preserves_search_gender_and_sort(): void
    {
        $wanted = $this->perfume();
        $this->perfume(['gender' => 'nam']);
        $this->get('/?search=Dior&gender=nu&sort=price_asc&min_price=1000')->assertOk()
            ->assertSee('name="search" value="Dior"', false)
            ->assertSee('name="gender" value="nu"', false)
            ->assertSee('name="sort" value="price_asc"', false)
            ->assertViewHas('perfumes', fn ($items) => $items->modelKeys() === [$wanted->id]);
    }

    public function test_empty_filters_keep_the_homepage_hero(): void
    {
        $this->get('/?search=&gender=&min_price=&max_price=')->assertOk()->assertSee('class="ht-hero', false);
    }

    public function test_only_actual_active_discounts_are_promoted(): void
    {
        $this->perfume(['sale_price' => 1200000]);
        $this->perfume(['sale_price' => 800000, 'is_active' => false]);
        $offer = $this->perfume(['sale_price' => 950000]);
        $this->get('/')->assertOk()->assertViewHas('featuredOffer', fn ($item) => $item->id === $offer->id)
            ->assertDontSee('flashCountdown')->assertSee('950.000');
        $this->get('/?sort=sale')->assertOk()
            ->assertViewHas('perfumes', fn ($items) => $items->modelKeys() === [$offer->id]);
    }

    public function test_invalid_search_and_price_range_are_validation_errors_instead_of_server_errors(): void
    {
        $this->getJson('/?search[]=Dior')->assertUnprocessable()->assertJsonValidationErrors('search');
        $this->getJson('/?min_price=100000&max_price=50000')->assertUnprocessable()->assertJsonValidationErrors('max_price');
    }

    public function test_welcome_popup_only_advertises_a_usable_coupon(): void
    {
        $this->get('/')->assertOk()->assertDontSee('id="ht-discount-popup"', false);
        $coupon = Coupon::create(['code' => 'HATHUFIRST', 'type' => 'percent', 'value' => 15,
            'minimum_order' => 500000, 'is_active' => true, 'expires_at' => now()->addDay()]);
        $this->get('/')->assertOk()->assertSee('id="ht-discount-popup"', false)->assertSee('15%')->assertSee('500.000₫');
        $coupon->update(['expires_at' => now()->subDay()]);
        $this->get('/')->assertOk()->assertDontSee('id="ht-discount-popup"', false);
    }
}
