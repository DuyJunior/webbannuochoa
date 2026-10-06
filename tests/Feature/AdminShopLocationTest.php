<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ShopLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminShopLocationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return ['name' => 'Shop Soopi', 'address' => 'Địa chỉ thử nghiệm mới',
            'latitude' => 21.03, 'longitude' => 105.81, 'hours' => '09:00 – 21:00', 'is_demo' => 1];
    }

    public function test_admin_can_save_and_update_one_location_everywhere(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('admin.shop-location.edit'))->assertOk()->assertSee('Lưu vị trí');
        $this->put(route('admin.shop-location.update'), $this->payload())->assertRedirect(route('admin.shop-location.edit'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('shop_locations', ['id' => 1, 'address' => 'Địa chỉ thử nghiệm mới']);
        foreach (['home', 'store.contact', 'store.faq'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Địa chỉ thử nghiệm mới')->assertSee('09:00 – 21:00');
        }
        $this->get(route('store.contact'))->assertSee('destination=21.03%2C105.81', false);
        $this->put(route('admin.shop-location.update'), array_merge($this->payload(), ['latitude' => -10.2, 'is_demo' => 0]));
        $this->assertDatabaseCount('shop_locations', 1);
        $this->get(route('store.contact'))->assertSee('data-latitude="-10.2"', false)->assertDontSee('Địa điểm minh họa cho bài tập');
    }

    public function test_visitors_customers_and_livestream_staff_cannot_change_location(): void
    {
        $this->get(route('admin.shop-location.edit'))->assertRedirect();
        $this->put(route('admin.shop-location.update'), $this->payload())->assertRedirect();
        foreach (['user', 'livestream_staff'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(route('admin.shop-location.edit'))->assertRedirect(route('home'));
            $this->put(route('admin.shop-location.update'), $this->payload())->assertRedirect(route('home'));
        }
        $this->assertDatabaseCount('shop_locations', 0);
    }

    public function test_invalid_input_does_not_overwrite_saved_location(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        ShopLocation::updateOrCreate(['id' => 1], $this->payload());
        foreach ([['latitude' => 91], ['longitude' => 181], ['latitude' => 'NaN'], ['name' => ''], ['address' => ''], ['hours' => str_repeat('x', 161)], ['is_demo' => 'wrong']] as $invalid) {
            $this->put(route('admin.shop-location.update'), array_merge($this->payload(), $invalid))->assertSessionHasErrors(array_keys($invalid));
        }
        $this->assertDatabaseHas('shop_locations', ['id' => 1, 'latitude' => 21.03, 'longitude' => 105.81]);
    }

    public function test_saved_location_text_is_escaped_in_public_and_admin_views(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->put(route('admin.shop-location.update'), array_merge($this->payload(), ['name' => '<script>bad()</script>', 'address' => '<img src=x onerror=bad()>']));
        foreach (['admin.shop-location.edit', 'store.contact'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('&lt;script&gt;bad()&lt;/script&gt;', false)
                ->assertDontSee('<script>bad()</script>', false)->assertDontSee('<img src=x onerror=bad()>', false);
        }
    }
}
