<?php

namespace Tests\Feature;

use App\Models\Perfume;
use App\Models\ScentWardrobe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StorefrontRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
    }

    private function perfume(string $slug, array $attributes = []): Perfume
    {
        return Perfume::create(array_merge([
            'name' => 'Mùi hương '.$slug, 'slug' => $slug, 'brand' => 'Soopi',
            'gender' => 'nu', 'volume_ml' => 100, 'weight' => 200, 'price' => 500000,
            'stock' => 5, 'is_active' => true, 'description' => 'Hương hoa dịu nhẹ.',
        ], $attributes));
    }

    public function test_wardrobes_render_after_a_saved_product_is_deleted_or_hidden_without_deleting_notes(): void
    {
        $user = User::factory()->create();
        $active = $this->perfume('available');
        $hidden = $this->perfume('hidden', ['is_active' => false]);
        $deleted = $this->perfume('deleted');
        foreach ([$active, $hidden, $deleted] as $perfume) {
            ScentWardrobe::create([
                'user_id' => $user->id, 'perfume_id' => $perfume->id,
                'occasion' => 'work', 'notes' => 'Ghi chú được giữ lại.',
            ]);
        }
        $deleted->delete();

        $this->actingAs($user)->get(route('store.wardrobe'))->assertOk()
            ->assertViewHas('wardrobeItems', fn ($items) => $items->pluck('perfume_id')->all() === [$active->id])
            ->assertViewHas('byOccasion', fn ($groups) => $groups['all']->count() === 1 && $groups['work']->count() === 1)
            ->assertSee($active->name)->assertDontSee($hidden->name)->assertDontSee($deleted->name);

        $this->app['auth']->forgetGuards();
        $this->get(route('store.wardrobe.share', $user))->assertOk()
            ->assertViewHas('wardrobeItems', fn ($items) => $items->pluck('perfume_id')->all() === [$active->id])
            ->assertSee($active->name)->assertDontSee($hidden->name)->assertDontSee($deleted->name);
        $this->assertDatabaseCount('scent_wardrobes', 3);
        $this->assertDatabaseHas('scent_wardrobes', ['perfume_id' => $deleted->id, 'notes' => 'Ghi chú được giữ lại.']);
    }

    public function test_wardrobes_have_an_empty_state_when_all_saved_products_are_unavailable(): void
    {
        $user = User::factory()->create();
        $perfume = $this->perfume('removed');
        ScentWardrobe::create(['user_id' => $user->id, 'perfume_id' => $perfume->id, 'occasion' => 'date']);
        $perfume->delete();

        $this->actingAs($user)->get(route('store.wardrobe'))->assertOk()
            ->assertViewHas('wardrobeItems', fn ($items) => $items->isEmpty())
            ->assertSee('ht-wardrobe-empty', false);
        $this->app['auth']->forgetGuards();
        $this->get(route('store.wardrobe.share', $user))->assertOk()
            ->assertViewHas('wardrobeItems', fn ($items) => $items->isEmpty())
            ->assertSee('ht-share-empty', false);
    }

    public function test_stock_failures_are_visible_on_every_storefront_page_with_a_direct_cart_form(): void
    {
        $user = User::factory()->create();
        $perfume = $this->perfume('sold-out', ['stock' => 0]);
        ScentWardrobe::create(['user_id' => $user->id, 'perfume_id' => $perfume->id, 'occasion' => 'work']);
        $this->actingAs($user);

        foreach ([
            route('store.gift-share', ['id' => $perfume->id]),
            route('store.scent-of-the-day'),
            route('store.compare', ['ids' => (string) $perfume->id]),
            route('store.wardrobe'),
        ] as $url) {
            $this->from($url)->post(route('cart.add', $perfume), ['quantity' => 1])
                ->assertRedirect($url)->assertSessionHasErrors('quantity');
            $message = session('errors')->first('quantity');
            $this->get($url)->assertOk()->assertSee('role="alert"', false)->assertSee($message);
        }
        Http::assertNothingSent();
    }
}
