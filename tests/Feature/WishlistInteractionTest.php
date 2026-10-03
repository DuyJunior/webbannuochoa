<?php

namespace Tests\Feature;

use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WishlistInteractionTest extends TestCase
{
    use RefreshDatabase;

    private function perfume(array $attributes = []): Perfume
    {
        return Perfume::create(array_merge([
            'name' => 'Rose Petal', 'slug' => 'rose-petal', 'brand' => 'Soopi',
            'gender' => 'nu', 'concentration' => 'EDP', 'volume_ml' => 100,
            'price' => 1200000, 'stock' => 3, 'is_active' => true,
        ], $attributes));
    }

    public function test_json_save_and_remove_are_idempotent_and_do_not_flash_success(): void
    {
        $user = User::factory()->create();
        $perfume = $this->perfume();
        $this->actingAs($user);
        $url = route('store.wishlist.toggle', $perfume);

        foreach ([true, true, false, false] as $saved) {
            $this->postJson($url, ['saved' => $saved])->assertOk()->assertExactJson([
                'perfume_id' => $perfume->id,
                'saved' => $saved,
                'message' => $saved ? 'Đã lưu mùi hương yêu thích.' : 'Đã bỏ sản phẩm khỏi danh sách yêu thích.',
            ])->assertSessionMissing('success');
            $this->assertDatabaseCount('wishlists', $saved ? 1 : 0);
        }
    }

    public function test_json_without_desired_state_preserves_toggle_behavior(): void
    {
        $user = User::factory()->create();
        $perfume = $this->perfume();
        $this->actingAs($user);

        $this->postJson(route('store.wishlist.toggle', $perfume))->assertOk()->assertJsonPath('saved', true);
        $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'perfume_id' => $perfume->id]);
        $this->postJson(route('store.wishlist.toggle', $perfume))->assertOk()->assertJsonPath('saved', false);
        $this->assertDatabaseCount('wishlists', 0);
    }

    public function test_form_encoded_desired_state_is_supported_for_ajax_requests(): void
    {
        $this->actingAs(User::factory()->create());
        $perfume = $this->perfume();

        foreach (['1', '1', '0', '0'] as $saved) {
            $this->post(route('store.wishlist.toggle', $perfume), ['saved' => $saved], ['Accept' => 'application/json'])
                ->assertOk()->assertJsonPath('saved', $saved === '1')->assertSessionMissing('success');
            $this->assertDatabaseCount('wishlists', (int) $saved);
        }
    }

    public function test_customer_cannot_change_another_customers_wishlist(): void
    {
        $other = User::factory()->create();
        $user = User::factory()->create();
        $perfume = $this->perfume();
        DB::table('wishlists')->insert([
            'user_id' => $other->id, 'perfume_id' => $perfume->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($user);

        $this->postJson(route('store.wishlist.toggle', $perfume), ['saved' => true, 'user_id' => $other->id])
            ->assertOk()->assertJsonPath('saved', true);
        $this->assertDatabaseCount('wishlists', 2);
        $this->postJson(route('store.wishlist.toggle', $perfume), ['saved' => false, 'user_id' => $other->id])
            ->assertOk()->assertJsonPath('saved', false);
        $this->assertDatabaseCount('wishlists', 1);
        $this->assertDatabaseHas('wishlists', ['user_id' => $other->id, 'perfume_id' => $perfume->id]);
        $this->assertDatabaseMissing('wishlists', ['user_id' => $user->id, 'perfume_id' => $perfume->id]);
    }

    public function test_guest_json_request_requires_login_without_changing_wishlist(): void
    {
        $perfume = $this->perfume();
        $this->postJson(route('store.wishlist.toggle', $perfume), ['saved' => true])
            ->assertUnauthorized()->assertSessionMissing('success');
        $this->assertDatabaseCount('wishlists', 0);
    }

    public function test_inactive_products_cannot_be_saved_or_removed(): void
    {
        $user = User::factory()->create();
        $perfume = $this->perfume(['is_active' => false]);
        $this->actingAs($user);

        $this->postJson(route('store.wishlist.toggle', $perfume), ['saved' => true])->assertNotFound();
        $this->assertDatabaseCount('wishlists', 0);
        DB::table('wishlists')->insert([
            'user_id' => $user->id, 'perfume_id' => $perfume->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->postJson(route('store.wishlist.toggle', $perfume), ['saved' => false])->assertNotFound();
        $this->assertDatabaseCount('wishlists', 1);
    }

    public function test_invalid_desired_state_does_not_toggle_existing_wishlist(): void
    {
        $user = User::factory()->create();
        $perfume = $this->perfume();
        $this->actingAs($user);
        DB::table('wishlists')->insert([
            'user_id' => $user->id, 'perfume_id' => $perfume->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ([null, 'false', 'yes', 2, ['saved' => true]] as $invalid) {
            $this->postJson(route('store.wishlist.toggle', $perfume), ['saved' => $invalid])
                ->assertUnprocessable()->assertJsonValidationErrors('saved');
            $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'perfume_id' => $perfume->id]);
        }
    }

    public function test_html_request_keeps_legacy_redirect_and_success_flash(): void
    {
        $user = User::factory()->create();
        $perfume = $this->perfume();
        $this->actingAs($user);
        $returnUrl = route('perfumes.show', $perfume);

        $this->from($returnUrl)->post(route('store.wishlist.toggle', $perfume))
            ->assertRedirect($returnUrl)->assertSessionHas('success', 'Đã lưu mùi hương yêu thích.');
        $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'perfume_id' => $perfume->id]);
        $this->from($returnUrl)->post(route('store.wishlist.toggle', $perfume))
            ->assertRedirect($returnUrl)->assertSessionHas('success', 'Đã bỏ sản phẩm khỏi danh sách yêu thích.');
        $this->assertDatabaseCount('wishlists', 0);
    }
}
