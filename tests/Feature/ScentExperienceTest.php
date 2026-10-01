<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Perfume;
use App\Models\User;
use App\Services\CartQuoteService;
use App\Services\MoodCollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScentExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function perfume(array $changes = []): Perfume
    {
        return Perfume::create(array_merge([
            'category_id' => Category::firstOrCreate(['name' => 'Hương hoa'])->id,
            'name' => 'Miss Dior Blooming Bouquet EDT', 'slug' => 'miss-dior-blooming-bouquet',
            'brand' => 'Dior', 'gender' => 'nu', 'concentration' => 'EDT',
            'volume_ml' => 100, 'price' => 3850000, 'sale_price' => 3390000,
            'stock' => 8, 'stock_10ml' => 2, 'stock_50ml' => 0, 'is_active' => true,
        ], $changes));
    }

    public function test_moods_only_offer_verified_active_and_in_stock_products_with_real_prices(): void
    {
        $perfume = $this->perfume(['stock' => 0, 'stock_10ml' => 3]);
        $inactive = clone $perfume;
        $inactive->is_active = false;
        $soldOut = clone $perfume;
        $soldOut->stock_10ml = 0;
        $unverified = clone $perfume;
        $unverified->name = 'Unknown fragrance';
        $groups = MoodCollectionService::from(collect([$perfume, $inactive, $soldOut, $unverified]));
        $this->assertCount(1, $groups['rose']);
        $this->assertCount(1, $groups['sage']);
        $this->assertCount(0, $groups['velvet']);
        $this->assertSame(10, $groups['rose'][0]['volume']);
        $this->assertSame(app(CartQuoteService::class)->unitPrice($perfume, 10), $groups['rose'][0]['price']);
        $this->assertNotEmpty($groups['rose'][0]['reason']);
    }

    public function test_product_notes_distinguish_verified_pyramid_keynotes_and_unknown_variants(): void
    {
        $this->withoutVite();
        $floral = $this->perfume();
        $this->get(route('perfumes.show', $floral))->assertOk()
            ->assertSee('data-fragrance-mode="keynotes"', false)->assertSee('Hoa hồng Damascus')
            ->assertDontSee('data-note-tab=', false)->assertDontSee('Pháp / Ý');
        $sauvage = $this->perfume(['name' => 'Dior Sauvage', 'slug' => 'dior-sauvage', 'concentration' => 'EDP']);
        $this->get(route('perfumes.show', $sauvage))->assertOk()
            ->assertSee('data-fragrance-mode="pyramid"', false)
            ->assertSee('data-note-tab="heart"', false)->assertSee('Tiêu Tứ Xuyên');
        $sauvage->update(['concentration' => 'EDT']);
        $this->get(route('perfumes.show', $sauvage))->assertOk()
            ->assertSee('data-fragrance-mode="pending"', false)->assertDontSee('data-note-tab=', false)
            ->assertDontSee('Vanilla Papua New Guinea');
    }

    public function test_confirmed_policy_and_contact_are_consistent_across_purchase_and_help(): void
    {
        $this->withoutVite();
        $product = $this->perfume();
        foreach ([route('home'), route('store.faq'), route('perfumes.show', $product)] as $url) {
            $this->get($url)->assertOk()->assertSee('https://zalo.me/0344216466', false)
                ->assertDontSee('0123456789')->assertDontSee('Đổi trả 14 ngày');
        }
        $this->actingAs(User::factory()->create())->withSession(['cart' => [$product->id => 1]])
            ->get(route('cart.index'))->assertOk()->assertSee('Đổi trả 7 ngày')->assertSee('#doi-tra', false)
            ->assertDontSee('Đổi trả 14 ngày')->assertDontSee('Hỗ trợ đổi mùi linh hoạt');
    }

    public function test_gallery_keeps_quick_view_fallback_links_after_filtering(): void
    {
        $this->withoutVite();
        $product = $this->perfume();
        $this->get('/?gender=nu')->assertOk()
            ->assertSee('data-quick-view="'.route('perfumes.quick-view', $product).'"', false)
            ->assertSee('href="'.route('perfumes.show', $product).'"', false)
            ->assertDontSee('data-mood-collection', false);
        $this->get('/')->assertOk()->assertSee('data-mood-collection', false)->assertSee('1 gợi ý · Dịu dàng');
    }
}
