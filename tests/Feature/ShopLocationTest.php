<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_contact_show_named_shop_map_and_same_directions(): void
    {
        $this->withoutVite();
        foreach (['home', 'store.contact'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertSee('data-shop-location', false)
                ->assertSee('Shop Soopi')
                ->assertSee('Sân vận động Mỹ Đình, đường Lê Đức Thọ, Hà Nội')
                ->assertSee('data-latitude="21.0205"', false)
                ->assertSee('data-longitude="105.76393"', false)
                ->assertSee('destination=21.0205%2C105.76393', false)
                ->assertSee('maps.google.com/maps?q=21.0205,105.76393', false)
                ->assertSee('không phải cửa hàng thực tế tại địa chỉ này.')
                ->assertDontSee('Đang dùng bản đồ Google dự phòng')
                ->assertDontSee('Thử lại bản đồ ghim Soopi')
                ->assertDontSee('41A Phú Diễn')
                ->assertDontSee('21.0461744');
        }
    }

    public function test_map_name_is_escaped(): void
    {
        $this->withoutVite();
        config(['storefront.location.name' => '<script>bad()</script>']);
        $this->get(route('store.contact'))->assertOk()
            ->assertSee('&lt;script&gt;bad()&lt;/script&gt;', false)
            ->assertDontSee('<script>bad()</script>', false);
    }
}
