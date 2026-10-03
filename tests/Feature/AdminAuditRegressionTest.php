<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Coupon;
use App\Models\Perfume;
use App\Models\Order;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminAuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Mail::fake();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function product(array $changes = []): Perfume
    {
        return Perfume::create(array_merge([
            'name' => 'Audit perfume', 'slug' => 'audit-perfume', 'brand' => 'Soopi',
            'gender' => 'unisex', 'volume_ml' => 100, 'price' => 500000,
            'stock' => 10, 'is_active' => true,
        ], $changes));
    }

    private function productInput(array $changes = []): array
    {
        return array_merge([
            'name' => 'Audit product', 'brand' => 'Soopi', 'gender' => 'unisex',
            'volume_ml' => 100, 'price' => 500000, 'stock' => 10, 'is_active' => 1,
        ], $changes);
    }

    private function createVideo(Perfume $product, array $changes = []): Video
    {
        $this->post(route('admin.videos.store'), array_merge([
            'title' => 'Audit product review', 'video_url' => 'videos/audit-review.mp4',
            'perfume_id' => $product->id, 'placement' => 'product', 'is_active' => 1,
        ], $changes))->assertSessionHasNoErrors()->assertRedirect(route('admin.videos.index'));

        return Video::latest('id')->firstOrFail();
    }

    public function test_product_create_and_update_reject_executable_video_urls(): void
    {
        $product = $this->product(['video_url' => 'videos/original.mp4']);
        foreach (['javascript:alert(1)', 'data:text/html,<script>alert(1)</script>', '//example.test/video.mp4', 'videos/../private.mp4'] as $url) {
            $this->postJson(route('admin.products.store'), $this->productInput(['video_url' => $url]))
                ->assertUnprocessable()->assertJsonValidationErrors('video_url');
            $this->putJson(route('admin.products.update', $product), $this->productInput(['video_url' => $url]))
                ->assertUnprocessable()->assertJsonValidationErrors('video_url');
        }
        $this->assertSame('videos/original.mp4', $product->fresh()->video_url);
        $this->assertDatabaseCount('perfumes', 1);

        foreach (['https://www.youtube.com/watch?v=abcdefghijk', '/videos/review.mp4'] as $url) {
            $this->put(route('admin.products.update', $product), $this->productInput(['video_url' => $url]))
                ->assertSessionHasNoErrors()->assertRedirect(route('admin.products.show', $product));
            $this->assertSame($url, $product->fresh()->video_url);
        }
    }

    public function test_product_nested_input_shows_validation_and_form_instead_of_crashing(): void
    {
        $url = route('admin.products.create');
        $this->from($url)->post(route('admin.products.store'), $this->productInput([
            'name' => ['bad'], 'brand' => ['bad'], 'image_url' => ['bad'],
        ]))->assertRedirect($url)->assertSessionHasErrors(['name', 'brand', 'image_url']);
        $this->get($url)->assertOk()->assertSee('name="name"', false);
        $this->assertDatabaseCount('perfumes', 0);
    }

    public function test_coupon_nested_code_and_out_of_range_integer_values_are_validation_errors(): void
    {
        $input = ['code' => 'AUDIT', 'type' => 'fixed', 'value' => 1000, 'minimum_order' => 0];
        $count = Coupon::count();
        $url = route('admin.coupons.index');
        $this->from($url)->post(route('admin.coupons.store'), array_merge($input, ['code' => ['bad']]))
            ->assertRedirect($url)->assertSessionHasErrors('code');
        $this->get($url)->assertOk()->assertSee('coupon-code');

        foreach (['value', 'minimum_order', 'usage_limit'] as $field) {
            $this->postJson(route('admin.coupons.store'), array_merge($input, [$field => 4294967296]))
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertDatabaseCount('coupons', $count);
    }

    public function test_video_counts_and_sort_order_respect_mysql_integer_boundaries(): void
    {
        $input = ['title' => 'Audit review', 'video_url' => 'videos/review.mp4', 'placement' => 'home'];
        $count = Video::count();
        foreach ([['views_count', 4294967296], ['sort_order', 2147483648], ['sort_order', -2147483649]] as [$field, $value]) {
            $this->postJson(route('admin.videos.store'), $input + [$field => $value])
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertDatabaseCount('videos', $count);
    }

    public function test_hiding_and_deleting_video_removes_copied_product_fallback(): void
    {
        $product = $this->product();
        $video = $this->createVideo($product);
        $this->assertSame($video->video_url, $product->fresh()->video_url);

        $this->post(route('admin.videos.toggle', $video))->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->video_url);
        $this->get(route('perfumes.show', $product))->assertOk()->assertDontSee('data-embed="videos/audit-review.mp4"', false);

        $this->post(route('admin.videos.toggle', $video))->assertSessionHasNoErrors();
        $this->assertSame($video->video_url, $product->fresh()->video_url);
        $this->delete(route('admin.videos.destroy', $video))->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->video_url);
        $this->get(route('perfumes.show', $product))->assertOk()->assertDontSee('data-embed="videos/audit-review.mp4"', false);
    }

    public function test_reassigning_video_clears_old_copy_and_keeps_independent_product_video(): void
    {
        $original = $this->product();
        $destination = $this->product(['slug' => 'other-perfume', 'video_url' => 'videos/independent.mp4']);
        $video = $this->createVideo($original);
        $this->put(route('admin.videos.update', $video), [
            'title' => $video->title, 'video_url' => 'videos/updated-review.mp4',
            'perfume_id' => $destination->id, 'placement' => 'all', 'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertNull($original->fresh()->video_url);
        $this->assertSame('videos/independent.mp4', $destination->fresh()->video_url);
        $this->delete(route('admin.videos.destroy', $video))->assertSessionHasNoErrors();
        $this->assertSame('videos/independent.mp4', $destination->fresh()->video_url);
    }

    public function test_home_only_and_inactive_videos_do_not_fill_product_fallback(): void
    {
        $product = $this->product();
        $this->createVideo($product, ['placement' => 'home']);
        $this->assertNull($product->fresh()->video_url);
        $this->createVideo($product, ['is_active' => 0]);
        $this->assertNull($product->fresh()->video_url);
    }

    public function test_admin_recent_customers_are_shared_by_team_and_sorted_by_last_message(): void
    {
        $firstAdmin = auth()->user();
        $secondAdmin = User::factory()->create(['role' => 'admin']);
        $olderCustomer = User::factory()->create();
        $newerCustomer = User::factory()->create();
        Message::create(['sender_id' => $newerCustomer->id, 'receiver_id' => $firstAdmin->id, 'content' => 'Earlier message']);
        Message::create(['sender_id' => $olderCustomer->id, 'receiver_id' => $firstAdmin->id, 'content' => 'Newest message']);

        $this->actingAs($secondAdmin)->getJson(route('admin.chat.users'))->assertOk()->assertJsonCount(2)
            ->assertJsonPath('0.id', $olderCustomer->id)->assertJsonPath('1.id', $newerCustomer->id);
        $this->getJson(route('admin.chat.messages', $olderCustomer))->assertOk()
            ->assertJsonFragment(['content' => 'Newest message']);
    }

    public function test_completed_status_cannot_collect_cod_with_conflicting_shipping_in_single_or_bulk_update(): void
    {
        foreach (['delivering', 'returned'] as $shipping) {
            $order = Order::create([
                'customer_name' => 'Audit customer', 'phone' => '0900000000', 'address' => 'Audit address',
                'total_price' => 500000, 'status' => 'cod_ordered', 'shipping_status' => 'pending',
            ]);
            $payment = $order->paymentTransactions()->create(['gateway' => 'cod', 'status' => 'pending', 'amount' => 500000]);

            $this->patch(route('admin.orders.update', $order), ['status' => 'completed', 'shipping_status' => $shipping])
                ->assertRedirect()->assertSessionHas('error');
            $this->post(route('admin.orders.bulk_update'), [
                'order_ids' => [$order->id], 'bulk_status' => 'completed', 'bulk_shipping_status' => $shipping,
            ])->assertRedirect()->assertSessionHas('success', fn ($message) => str_contains($message, '0 đơn hàng'));

            $this->assertSame('cod_ordered', $order->fresh()->status);
            $this->assertSame('pending', $order->fresh()->shipping_status);
            $this->assertSame('pending', $payment->fresh()->status);
            $this->assertNull($payment->fresh()->paid_at);
        }
    }
}
