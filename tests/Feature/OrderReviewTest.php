<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Perfume;
use App\Models\PerfumeReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OrderReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function product(string $slug = 'review-rose'): Perfume
    {
        return Perfume::create(['name' => 'Miss Dior Blooming Bouquet', 'slug' => $slug, 'brand' => 'Dior', 'gender' => 'nu',
            'volume_ml' => 100, 'price' => 1200000, 'stock' => 10, 'is_active' => true, 'image_url' => 'images/gallery/miss-dior.webp']);
    }

    private function purchase(User $user, Perfume $product, array $attributes = []): Order
    {
        $order = Order::create(array_merge(['user_id' => $user->id, 'customer_name' => 'Ngọc Linh', 'phone' => '0900000000',
            'address' => 'Hà Nội', 'total_price' => 1200000, 'status' => 'completed', 'shipping_status' => 'delivered'], $attributes));
        $order->items()->create(['perfume_id' => $product->id, 'quantity' => 1, 'price' => 1200000, 'volume_ml' => 100]);

        return $order;
    }

    private function data(Order $order, Perfume $product): array
    {
        return ['order_id' => $order->id, 'order_item_id' => $order->items->first()->id,
            'review_key' => $order->id.'-'.$order->items->first()->id.'-'.$product->id,
            'rating' => 4, 'body' => 'Hương thơm dịu nhẹ, mình rất hài lòng.'];
    }

    public function test_delivered_purchase_can_be_reviewed_from_history_and_detail_then_edited(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $order = $this->purchase($user, $product);
        $data = $this->data($order, $product);
        $this->actingAs($user);
        foreach (['orders.index', 'orders.show'] as $route) {
            $this->get(route($route, $route === 'orders.show' ? $order : []))->assertOk()->assertSee('Đánh giá sản phẩm')
                ->assertSee(route('store.review', $product), false)->assertSee('name="order_item_id"', false);
        }
        $this->from(route('orders.index'))->post(route('store.review', $product), $data)
            ->assertRedirect(route('orders.index').'#review-'.$data['review_key'])->assertSessionHas('review_saved', $data['review_key']);
        $this->get(route('orders.index'))->assertSee('Đã đánh giá')->assertSee('Sửa đánh giá')->assertSee('4/5');
        $this->get(route('perfumes.show', $product))->assertSee($data['body']);
        $data['rating'] = 5;
        $this->from(route('orders.show', $order))->post(route('store.review', $product), $data)
            ->assertRedirect(route('orders.show', $order).'#review-'.$data['review_key']);
        $this->assertSame(1, PerfumeReview::where('user_id', $user->id)->where('perfume_id', $product->id)->count());
        $this->assertDatabaseHas('perfume_reviews', ['user_id' => $user->id, 'perfume_id' => $product->id, 'rating' => 5]);

        if (getenv('SOOPI_REVIEW_EXPORT') === '1') {
            $this->withVite();
            foreach (['vi', 'en'] as $locale) {
                $this->withSession(['locale' => $locale]);
                foreach (['orders.index', 'orders.show'] as $route) {
                    file_put_contents(storage_path('app/review-'.$locale.'-'.$route.'.html'), $this->get(route($route, $route === 'orders.show' ? $order : []))->getContent());
                }
            }
        }
    }

    public function test_pending_paid_shipping_and_cancelled_orders_cannot_be_reviewed(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->actingAs($user);
        foreach ([['pending', 'pending'], ['paid', 'delivering'], ['cancelled', 'delivered']] as [$status, $shipping]) {
            $order = $this->purchase($user, $product, ['status' => $status, 'shipping_status' => $shipping]);
            $this->get(route('orders.show', $order))->assertOk()->assertDontSee('class="order-review-form"', false);
            $this->post(route('store.review', $product), $this->data($order, $product))->assertSessionHasErrors('review');
        }
        $this->assertDatabaseCount('perfume_reviews', 0);
    }

    public function test_another_users_order_or_an_unrelated_item_cannot_authorize_a_review(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $otherProduct = $this->product('another-product');
        $otherOrder = $this->purchase(User::factory()->create(), $product);
        $ownedOrder = $this->purchase($user, $product);
        $this->actingAs($user)->get(route('orders.show', $otherOrder))->assertForbidden();
        $this->post(route('store.review', $product), $this->data($otherOrder, $product))->assertSessionHasErrors('review');
        $this->post(route('store.review', $otherProduct), $this->data($ownedOrder, $otherProduct))->assertSessionHasErrors('review');
        $forged = $this->data($ownedOrder, $product);
        $forged['order_item_id'] = $otherOrder->items->first()->id;
        $this->post(route('store.review', $product), $forged)->assertSessionHasErrors('review');
        $this->assertDatabaseCount('perfume_reviews', 0);
    }

    public function test_review_validation_returns_to_the_same_form_and_keeps_input(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $order = $this->purchase($user, $product);
        $data = $this->data($order, $product);
        $data['rating'] = [5];
        $data['body'] = 'Ngắn';
        $data['image'] = UploadedFile::fake()->create('not-an-image.txt', 5, 'text/plain');
        $this->actingAs($user)->from(route('orders.index'))->post(route('store.review', $product), $data)
            ->assertRedirect(route('orders.index').'#review-'.$data['review_key'])->assertSessionHasErrors(['rating', 'body', 'image'])
            ->assertSessionHasInput('body', 'Ngắn');
        $this->get(route('orders.index'))->assertOk()->assertSee('class="order-review-details"  open', false)->assertSee('Cảm nhận cần ít nhất 10 ký tự.');
        $this->assertDatabaseCount('perfume_reviews', 0);
    }

    public function test_samples_in_discovery_and_gift_bundles_can_be_reviewed_individually(): void
    {
        $user = User::factory()->create();
        $main = $this->product();
        $sample = $this->product('sample-product');
        $order = $this->purchase($user, $main);
        $order->items->first()->update(['stock_components' => [
            ['perfume_id' => $main->id, 'volume_ml' => 100, 'role' => 'main'],
            ['perfume_id' => $sample->id, 'volume_ml' => 5, 'role' => 'sample'],
        ]]);
        $this->actingAs($user)->get(route('orders.show', $order))->assertOk()->assertSee(route('store.review', $sample), false);
        $this->post(route('store.review', $sample), $this->data($order, $sample))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('perfume_reviews', ['user_id' => $user->id, 'perfume_id' => $sample->id]);
        $order->items->first()->update(['stock_components' => [
            ['perfume_id' => $main->id, 'volume_ml' => 5], ['perfume_id' => $sample->id, 'volume_ml' => 5],
        ]]);
        $this->get(route('orders.show', $order))->assertOk()->assertSee(route('store.review', $sample), false);
    }

    public function test_inactive_or_deleted_products_do_not_show_broken_review_forms(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $order = $this->purchase($user, $product);
        $product->update(['is_active' => false]);
        $this->actingAs($user)->get(route('orders.show', $order))->assertOk()->assertSee('Sản phẩm này hiện không nhận đánh giá.')->assertDontSee('class="order-review-form"', false);
        $product->delete();
        $this->get(route('orders.index'))->assertOk()->assertDontSee('class="order-review-form"', false);
    }
}
