<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Perfume;
use App\Models\PerfumeReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CustomerExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function photo(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    }

    private function product(): Perfume
    {
        $this->withoutVite();

        return Perfume::create(['name'=>'Miss Dior Blooming Bouquet EDT', 'slug'=>'chic-review', 'brand'=>'Dior',
            'gender'=>'nu', 'volume_ml'=>100, 'price'=>3390000, 'stock'=>10, 'is_active'=>true,
            'image_url'=>'images/gallery/miss-dior.webp']);
    }

    private function purchase(User $user, Perfume $product, string $shipping = 'delivered'): Order
    {
        $order = Order::create(['user_id'=>$user->id, 'name'=>'Khách trải nghiệm', 'phone'=>'0900000000',
            'address'=>'Địa chỉ minh họa · Hà Nội', 'total_price'=>3439500, 'ghn_total_fee'=>49500,
            'status'=>$shipping === 'delivered' ? 'completed' : 'cod_ordered', 'shipping_status'=>$shipping]);
        $order->items()->create(['perfume_id'=>$product->id, 'price'=>3390000, 'quantity'=>1, 'volume_ml'=>100]);

        return $order;
    }

    public function test_review_statistics_cover_all_pages_and_verified_badges_require_a_delivered_purchase(): void
    {
        $product = $this->product();
        $buyer = User::factory()->create(['name'=>'Ngọc Linh']);
        $order = $this->purchase($buyer, $product);
        foreach ([5,5,4,5,4,5] as $index => $rating) {
            $author = $index === 5 ? $buyer : User::factory()->create(['name'=>['Minh Anh','Thu Hà','Khánh An','Bảo Ngọc','Mai Chi'][$index]]);
            // Legacy reviews without purchase proof must not receive a verified badge.
            $entry = PerfumeReview::create(['user_id'=>$author->id,'perfume_id'=>$product->id,'rating'=>$rating,
                'body'=>'Hương hoa dịu nhẹ, gói hàng chỉn chu. Một mùi hương mình rất thích dùng mỗi sáng.',
                'tags'=>['packaging','scent'], 'image_path'=>$index === 5 ? 'images/journal/notes.webp' : null,
                'seller_reply'=>$index === 5 ? 'Soopi cảm ơn bạn đã chia sẻ. Mong mùi hương sẽ đồng hành cùng những ngày thật đẹp.' : null,
                'replied_at'=>$index === 5 ? now() : null, 'created_at'=>now()->addSeconds($index)]);
            $entry->forceFill(['created_at'=>now()->addSeconds($index)])->save();
        }
        $response = $this->actingAs($buyer)->get(route('perfumes.show', $product))->assertOk()->assertSee('4.7')->assertSee('100%');
        $this->assertEquals([4=>2,5=>4], $response->viewData('ratingCounts')->all());
        $this->assertCount(5, $response->viewData('reviews'));
        $this->assertEquals([$buyer->id], $response->viewData('verifiedBuyerIds')->values()->all());
        $response->assertSee('Đã mua hàng')->assertSee('Phản hồi từ Soopi')->assertSee('name="images[]"',false);
        $this->get(route('perfumes.show', $product).'?page=2')->assertOk()->assertSee('4.7');

        if (getenv('SOOPI_CHIC_EXPORT') === '1') {
            $this->purchase($buyer,$product,'delivering');
            $this->purchase($buyer,$product,'ready_to_pick');
            $this->withVite();
            foreach (['vi','en'] as $locale) {
                $this->withSession(['locale'=>$locale]);
                foreach (['orders'=>route('orders.index'), 'product'=>route('perfumes.show',$product), 'detail'=>route('orders.show',$order)] as $name=>$url) {
                    file_put_contents(storage_path('app/chic-'.$locale.'-'.$name.'.html'), $this->get($url)->assertOk()->getContent());
                }
            }
        }
    }

    public function test_images_and_tags_are_validated_saved_and_preserved_when_editing_without_new_images(): void
    {
        $product=$this->product(); $buyer=User::factory()->create(); $this->purchase($buyer,$product); $this->actingAs($buyer);
        $base=['rating'=>4,'body'=>'Trải nghiệm rất tốt và mình sẽ quay lại.', 'tags'=>['packaging','delivery']];
        $this->post(route('store.review',$product), $base+['images'=>[UploadedFile::fake()->create('fake.svg',1,'image/svg+xml')]])->assertSessionHasErrors('images.0');
        $this->post(route('store.review',$product), array_merge($base,['tags'=>['not-a-real-tag']]))->assertSessionHasErrors('tags.0');
        $this->post(route('store.review',$product), $base+['images'=>array_fill(0,5,$this->photo('photo.png'))])->assertSessionHasErrors('images');
        $this->post(route('store.review',$product), $base+['images'=>[$this->photo('one.png'), $this->photo('two.png')]])->assertSessionHasNoErrors();
        $review=PerfumeReview::firstOrFail(); $paths=$review->photos;
        try {
            $this->assertCount(2,$paths);
            $this->assertEquals(['packaging','delivery'],$review->tags);
            $this->assertEquals($paths[0],$review->image_path);
            foreach($paths as $path) $this->assertFileExists(public_path($path));
            $this->post(route('store.review',$product), ['rating'=>5,'body'=>'Cập nhật sau một tuần, mình vẫn rất thích.'])->assertSessionHasNoErrors();
            $this->assertEquals($paths,$review->fresh()->photos);
            $this->assertEquals([],$review->fresh()->tags);
        } finally {
            foreach($paths as $path) @unlink(public_path($path));
        }
    }

    public function test_only_admin_can_save_seller_reply_and_markup_is_escaped(): void
    {
        $product=$this->product();$buyer=User::factory()->create();
        $review=PerfumeReview::create(['user_id'=>$buyer->id,'perfume_id'=>$product->id,'rating'=>5,'body'=>'A genuinely lovely fragrance.']);
        $url=route('admin.reviews.reply',$review);
        $this->actingAs($buyer)->post($url,['seller_reply'=>'Forged seller response'])->assertRedirect(route('home'));
        $this->assertNull($review->fresh()->seller_reply);
        $admin=User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->post($url,['seller_reply'=>'<script>alert(1)</script>'])->assertSessionHasNoErrors();
        $this->get(route('perfumes.show',$product))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false)->assertDontSee('<script>alert(1)</script>',false);
        $this->assertNotNull($review->fresh()->replied_at);
    }

    public function test_abnormal_or_cancelled_orders_never_show_a_successful_progress_bar(): void
    {
        $product=$this->product();$buyer=User::factory()->create();$order=$this->purchase($buyer,$product,'returned');$this->actingAs($buyer);
        foreach(['returned','cancelled','exception','unknown-state'] as $state){
            $order->update(['shipping_status'=>$state]);
            $this->get(route('orders.index'))->assertOk()->assertDontSee('class="ol-stepper"',false);
        }
        $order->update(['shipping_status'=>'delivered','status'=>'cancelled']);
        $this->get(route('orders.index'))->assertSee('Đã hủy')->assertDontSee('class="ol-stepper"',false);
        $order->update(['status'=>'completed']);
        $this->get(route('orders.index'))->assertSee('aria-current="step"',false)->assertSee('Giao thành công');
    }

    public function test_empty_reviews_and_non_buyers_do_not_show_made_up_scores_or_submission_form(): void
    {
        $product=$this->product();$buyer=User::factory()->create();
        $this->actingAs($buyer)->get(route('perfumes.show',$product))->assertOk()->assertSee('Câu chuyện đầu tiên là của bạn')
            ->assertDontSee('class="rv-form"',false)->assertViewHas('averageRating',0);
        $this->get(route('orders.index'))->assertOk()->assertSee('Bạn chưa có đơn hàng nào');
    }
}
