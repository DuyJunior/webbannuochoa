<?php

namespace Tests\Feature;

use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiftFinderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_gift_flow_keeps_message_private_and_shows_relevant_results_first(): void
    {
        foreach ([['Woman', 'nu', 2000000, 2], ['Unisex', 'unisex', 3000000, 1], ['Man', 'nam', 1000000, 2], ['Expensive', 'nu', 5000000, 2], ['Sold out', 'nu', 1000000, 0]] as [$name, $gender, $price, $stock]) {
            Perfume::create(compact('name', 'gender', 'price', 'stock') + ['brand' => 'Soopi', 'slug' => str_replace(' ', '-', strtolower($name)), 'volume_ml' => 100, 'is_active' => true]);
        }
        $message = 'TỚ YÊU CẬU <script>alert(1)</script>';
        $response = $this->post(route('store.gift-finder'), ['gender' => 'nu', 'occasion' => 'tiec', 'max_price' => 4000000, 'gift_message' => $message]);
        $response->assertRedirect(route('store.finder', ['gift' => 1, 'occasion' => 'tiec', 'gender' => 'nu', 'max_price' => 4000000]))->assertSessionHas('gift_finder_message', $message);
        $this->assertStringNotContainsString('gift_message', $response->headers->get('Location'));
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('Quà tặng dành cho nàng')->assertSee('4.000.000₫')
            ->assertSee('Sinh nhật / dịp đặc biệt')->assertSee(e($message), false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('Một cuộc hẹn.')->assertViewHas('recommended', fn ($items) => $items->pluck('name')->sort()->values()->all() === ['Unisex', 'Woman']);
        $this->withSession(['locale' => 'en'])->get('/chon-huong?gift=1&gender=nu&occasion=tiec&max_price=4000000')->assertSee('Gifts for her');
        $this->get('/chon-huong')->assertOk()->assertDontSee('gift-finder-heading');
    }

    public function test_gift_draft_validation_clearing_and_checkout_prefill(): void
    {
        $this->postJson('/chon-qua', ['gift_message' => ['invalid']])->assertUnprocessable();
        $this->postJson('/chon-qua', ['gift_message' => str_repeat('x', 121)])->assertUnprocessable();
        $this->post('/chon-qua', ['gift_message' => 'TỚ YÊU CẬU'])->assertSessionHas('gift_finder_message', 'TỚ YÊU CẬU');
        $product = Perfume::create(['name' => 'Gift perfume', 'brand' => 'Soopi', 'slug' => 'gift-perfume', 'gender' => 'nu', 'price' => 1000000, 'stock' => 3, 'volume_ml' => 100, 'is_active' => true]);
        $this->get('/perfumes/'.$product->id.'?gift=1')->assertOk()->assertSee('TỚ YÊU CẬU');
        $this->actingAs(User::factory()->create())->post(route('cart.add', $product), ['quantity' => 1, 'volume_ml' => 100])->assertSessionHasNoErrors();
        $page = $this->get(route('payment.index'))->assertOk()->assertSee('TỚ YÊU CẬU');
        $this->assertMatchesRegularExpression('/<textarea[^>]*id="gift_message"[^>]*disabled[^>]*>TỚ YÊU CẬU<\/textarea>/', $page->getContent());
        $this->post('/chon-qua', ['gift_message' => ''])->assertSessionHas('gift_finder_message', '');
    }
}
