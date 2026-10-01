<?php

namespace Tests\Feature;

use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_is_redirected_to_login_when_accessing_cart(): void
    {
        $this->get(route('cart.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_adding_product_to_cart(): void
    {
        $perfume = Perfume::create($this->perfumeData());

        $this->post(route('cart.add', $perfume), ['quantity' => 1])
            ->assertRedirect(route('login'));
    }

    public function test_product_detail_contains_purchase_controls(): void
    {
        $perfume = Perfume::create($this->perfumeData());

        $this->get(route('perfumes.show', $perfume))
            ->assertOk()
            ->assertSee('Dung tích')
            ->assertSee('100ml')
            ->assertSee('Số lượng')
            ->assertSee('Thêm vào giỏ')
            ->assertSee('Mua ngay');
    }

    public function test_product_can_be_added_to_cart_by_authenticated_user(): void
    {
        $user = User::factory()->create();
        $perfume = Perfume::create($this->perfumeData());

        $this->actingAs($user)
            ->post(route('cart.add', $perfume), ['quantity' => 2])
            ->assertRedirect()
            ->assertSessionHas('cart.'.$perfume->id, 2);

        $this->actingAs($user)
            ->withSession(['cart' => [$perfume->id => 2]])
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee($perfume->name)
            ->assertSee('100ml')
            ->assertSee('7.000.000₫')
            ->assertSee('Chọn tất cả')
            ->assertSee('name="selected_items[]"', false);
    }

    public function test_cart_quantity_cannot_exceed_stock(): void
    {
        $user = User::factory()->create();
        $perfume = Perfume::create($this->perfumeData(['stock' => 3]));

        $this->actingAs($user)
            ->post(route('cart.add', $perfume), ['quantity' => 4])
            ->assertSessionHasErrors('quantity');

        $this->assertEmpty(session('cart', []));
    }

    public function test_checkout_reduces_stock_and_clears_cart(): void
    {
        $user = User::factory()->create();
        $perfume = Perfume::create($this->perfumeData(['stock' => 5]));

        $this->actingAs($user)
            ->withSession(['cart' => [$perfume->id => 2]])
            ->post(route('cart.checkout'), [
                'customer_name' => 'Nguyễn Văn A',
                'phone' => '0901234567',
                'address' => '123 Nguyễn Trãi, Hà Nội',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionMissing('cart');

        $this->assertDatabaseHas('perfumes', [
            'id' => $perfume->id,
            'stock' => 3,
        ]);
    }

    public function test_checkout_only_selected_items(): void
    {
        $user = User::factory()->create();
        $perfume1 = Perfume::create($this->perfumeData(['name' => 'Perfume 1', 'slug' => 'perfume-1', 'stock' => 10]));
        $perfume2 = Perfume::create($this->perfumeData(['name' => 'Perfume 2', 'slug' => 'perfume-2', 'stock' => 10]));

        $this->actingAs($user)
            ->withSession([
                'cart' => [
                    (string) $perfume1->id => 2,
                    (string) $perfume2->id => 3,
                ]
            ])
            ->post(route('cart.checkout'), [
                'customer_name' => 'Nguyễn Văn B',
                'phone' => '0912345678',
                'address' => '456 Cầu Giấy, Hà Nội',
                'selected_items' => [(string) $perfume1->id],
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('cart', [(string) $perfume2->id => 3]);

        // perfume1 stock decremented by 2 (10 -> 8)
        $this->assertDatabaseHas('perfumes', [
            'id' => $perfume1->id,
            'stock' => 8,
        ]);

        // perfume2 stock unchanged (10)
        $this->assertDatabaseHas('perfumes', [
            'id' => $perfume2->id,
            'stock' => 10,
        ]);
    }

    public function test_checkout_fails_when_selected_items_is_empty(): void
    {
        $user = User::factory()->create();
        $perfume = Perfume::create($this->perfumeData(['stock' => 5]));

        $this->actingAs($user)
            ->withSession(['cart' => [(string) $perfume->id => 2]])
            ->post(route('cart.checkout'), [
                'customer_name' => 'Nguyễn Văn A',
                'phone' => '0901234567',
                'address' => '123 Nguyễn Trãi, Hà Nội',
                'selected_items' => [],
            ])
            ->assertSessionHasErrors('selected_items');
    }

    public function test_legacy_checkout_rejects_missing_stale_and_malformed_explicit_selections_without_ordering(): void
    {
        $user = User::factory()->create();
        $perfume = Perfume::create($this->perfumeData(['stock' => 5]));
        $cart = [$perfume->id => 2];
        $address = ['customer_name' => 'Nguyễn Văn A', 'phone' => '0901234567', 'address' => '123 Nguyễn Trãi, Hà Nội'];

        foreach ([
            ['selection' => 1],
            ['selection' => 1, 'selected_items' => []],
            ['selected_items' => ['removed-item']],
            ['selected_items' => [(string) $perfume->id, 'removed-item']],
            ['selected_items' => [(string) $perfume->id, (string) $perfume->id]],
            ['selected_items' => [['nested-item']]],
            ['selected_items' => (string) $perfume->id],
        ] as $selection) {
            $this->actingAs($user)->withSession(['cart' => $cart])
                ->postJson(route('cart.checkout'), array_merge($address, $selection))
                ->assertUnprocessable()->assertSessionHas('cart', $cart);

            $this->assertDatabaseCount('orders', 0);
            $this->assertDatabaseCount('order_items', 0);
            $this->assertDatabaseCount('inventory_movements', 0);
            $this->assertSame(5, $perfume->fresh()->stock);
        }
    }

    public function test_checkout_rejects_phone_number_not_strictly_10_digits(): void
    {
        $user = User::factory()->create();
        $perfume = Perfume::create($this->perfumeData(['stock' => 5]));

        $invalidPhones = ['091234567', '09123456789', '1234567890', '091234abcd'];

        foreach ($invalidPhones as $invalidPhone) {
            $response = $this->actingAs($user)
                ->withSession(['cart' => [(string) $perfume->id => 1]])
                ->post(route('cart.checkout'), [
                    'customer_name' => 'Nguyễn Văn A',
                    'phone' => $invalidPhone,
                    'address' => '123 Nguyễn Trãi, Hà Nội',
                ]);

            $response->assertSessionHasErrors(['phone']);
        }
    }

    private function perfumeData(array $overrides = []): array
    {
        return array_merge([
            'category_id' => null,
            'name' => 'Bleu de Chanel',
            'slug' => 'bleu-de-chanel',
            'brand' => 'Chanel',
            'gender' => 'nam',
            'concentration' => 'EDP',
            'volume_ml' => 100,
            'weight' => 200,
            'price' => 3900000,
            'sale_price' => 3500000,
            'stock' => 12,
            'image_url' => 'https://example.com/bleu-de-chanel.jpg',
            'description' => 'Hương gỗ thơm nam tính và thanh lịch.',
            'is_active' => true,
        ], $overrides);
    }
}

