<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Perfume;
use App\Models\PerfumeReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseScopedReviewTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Perfume
    {
        $this->withoutVite();
        return Perfume::create(['name'=>'Miss Dior Blooming Bouquet EDT','slug'=>'repeat-purchase','brand'=>'Dior',
            'gender'=>'nu','volume_ml'=>100,'price'=>3390000,'stock'=>10,'is_active'=>true,'image_url'=>'images/gallery/miss-dior.webp']);
    }

    private function purchase(User $user, Perfume $product, string $status = 'completed'): Order
    {
        $order=Order::create(['user_id'=>$user->id,'name'=>'Khách trải nghiệm','phone'=>'0900000000',
            'address'=>'Hà Nội','total_price'=>750000,'status'=>$status,'shipping_status'=>$status === 'completed' ? 'delivered' : 'pending']);
        $order->items()->create(['perfume_id'=>$product->id,'price'=>750000,'quantity'=>1,'volume_ml'=>10]);
        return $order;
    }

    private function payload(Order $order): array
    {
        return ['order_id'=>$order->id,'order_item_id'=>$order->items->first()->id,'rating'=>5,'body'=>'Hương thơm rất dễ chịu, gói hàng cẩn thận.'];
    }

    public function test_a_repeat_purchase_starts_blank_and_does_not_overwrite_an_earlier_review(): void
    {
        $product=$this->product();$buyer=User::factory()->create();$first=$this->purchase($buyer,$product);$this->actingAs($buyer);
        $this->post(route('store.review',$product),array_merge($this->payload($first),['rating'=>1,'body'=>'Lần mua đầu tiên mình chưa thấy phù hợp.']))->assertSessionHasNoErrors();
        $second=$this->purchase($buyer,$product);
        $this->get(route('orders.show',$second))->assertOk()->assertSee('Đánh giá sản phẩm')->assertDontSee('Sửa đánh giá')->assertDontSee('Lần mua đầu tiên mình chưa thấy phù hợp.');
        $this->get(route('orders.show',$first))->assertOk()->assertSee('Sửa đánh giá')->assertSee('1/5');
        $this->post(route('store.review',$product),$this->payload($second))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('perfume_reviews',2);
        $this->assertDatabaseHas('perfume_reviews',['order_item_id'=>$first->items->first()->id,'rating'=>1]);
        $this->assertDatabaseHas('perfume_reviews',['order_item_id'=>$second->items->first()->id,'rating'=>5]);
        $this->post(route('store.review',$product),array_merge($this->payload($second),['rating'=>4]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('perfume_reviews',2);
        $this->assertDatabaseHas('perfume_reviews',['order_item_id'=>$first->items->first()->id,'rating'=>1]);
        $this->get(route('perfumes.show',$product))->assertOk()->assertViewHas('averageRating',2.5);
    }

    public function test_product_form_explicitly_identifies_purchase_and_ambiguous_legacy_posts_are_rejected(): void
    {
        $product=$this->product();$buyer=User::factory()->create();$first=$this->purchase($buyer,$product);$second=$this->purchase($buyer,$product);$this->actingAs($buyer);
        $response=$this->get(route('perfumes.show',$product))->assertOk()->assertSee('ĐÁNH GIÁ CHO LẦN MUA');
        $this->assertEquals($second->items->first()->id,$response->viewData('reviewPurchase')->id);
        $response=$this->get(route('perfumes.show',['perfume'=>$product,'review_item'=>$first->items->first()->id]))->assertOk();
        $this->assertEquals($first->items->first()->id,$response->viewData('reviewPurchase')->id);
        $this->post(route('store.review',$product),['rating'=>4,'body'=>'Không được tự chọn nhầm đơn để đánh giá.'])->assertSessionHasErrors('review');
        $this->assertDatabaseCount('perfume_reviews',0);
        $url=route('perfumes.show',['perfume'=>$product,'review_item'=>$first->items->first()->id]);
        $this->from($url)->post(route('store.review',$product),$this->payload($first)+['review_source'=>'product'])
            ->assertRedirect($url.'#review-compose')->assertSessionHasNoErrors();
    }

    public function test_old_reviews_and_images_remain_separate_and_can_be_edited_only_by_the_owner(): void
    {
        $product=$this->product();$buyer=User::factory()->create();$old=PerfumeReview::create(['user_id'=>$buyer->id,'perfume_id'=>$product->id,
            'rating'=>1,'body'=>'Đánh giá cũ cần được giữ nguyên nội dung.','image_path'=>'images/reviews/legacy-photo.jpg','seller_reply'=>'Phản hồi cũ được giữ nguyên.']);
        $order=$this->purchase($buyer,$product);$this->actingAs($buyer);
        $this->get(route('orders.show',$order))->assertOk()->assertSee('Đánh giá sản phẩm')->assertDontSee('Sửa đánh giá');
        $this->post(route('store.review',$product),$this->payload($order))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('perfume_reviews',2);
        $this->assertEquals(1,$old->fresh()->rating);
        $this->get(route('perfumes.show',['perfume'=>$product,'legacy_review'=>$old->id]))->assertOk()->assertSee('Đánh giá trước đây');
        $this->post(route('store.review',$product),['legacy_review_id'=>$old->id,'rating'=>2,'body'=>'Mình bổ sung cảm nhận cho đánh giá cũ.'])->assertSessionHasNoErrors();
        $this->assertEquals('images/reviews/legacy-photo.jpg',$old->fresh()->image_path);
        $this->assertEquals('Phản hồi cũ được giữ nguyên.',$old->fresh()->seller_reply);
        $this->assertNull($old->fresh()->order_item_id);
        $other=User::factory()->create();$this->purchase($other,$product);$this->actingAs($other);
        $this->post(route('store.review',$product),['legacy_review_id'=>$old->id,'rating'=>5,'body'=>'Nội dung không được ghi lên đánh giá người khác.'])->assertSessionHasErrors('review');
        $this->get(route('perfumes.show',['perfume'=>$product,'legacy_review'=>$old->id]))->assertNotFound();
        $this->assertEquals(2,$old->fresh()->rating);
    }

    public function test_another_order_and_an_undelivered_repeat_purchase_cannot_be_used_to_edit_a_review(): void
    {
        $product=$this->product();$buyer=User::factory()->create();$first=$this->purchase($buyer,$product);$pending=$this->purchase($buyer,$product,'pending');
        $foreign=$this->purchase(User::factory()->create(),$product);$this->actingAs($buyer);
        $this->post(route('store.review',$product),$this->payload($first))->assertSessionHasNoErrors();
        foreach([$pending,$foreign] as $invalid){
            $this->post(route('store.review',$product),$this->payload($invalid))->assertSessionHasErrors('review');
            $this->get(route('perfumes.show',['perfume'=>$product,'review_item'=>$invalid->items->first()->id]))->assertNotFound();
        }
        $forged=$this->payload($first);$forged['order_item_id']=$foreign->items->first()->id;
        $this->post(route('store.review',$product),$forged)->assertSessionHasErrors('review');
        $this->assertDatabaseCount('perfume_reviews',1);
    }

    public function test_migration_keeps_legacy_data_without_assigning_it_to_a_new_purchase(): void
    {
        $product=$this->product();$buyer=User::factory()->create();
        $old=PerfumeReview::create(['user_id'=>$buyer->id,'perfume_id'=>$product->id,'rating'=>3,'body'=>'Nội dung lịch sử cần được bảo toàn.',
            'images'=>['images/reviews/old.jpg'],'tags'=>['packaging'],'seller_reply'=>'Cảm ơn bạn.']);
        $migration=require database_path('migrations/2026_10_08_140000_scope_reviews_to_order_items.php');
        $migration->down();$migration->up();
        $this->assertNull($old->fresh()->order_item_id);
        $this->assertSame(['images/reviews/old.jpg'],$old->fresh()->photos);
        $this->assertSame(['packaging'],$old->fresh()->tags);
        $this->assertSame('Cảm ơn bạn.',$old->fresh()->seller_reply);
        $this->assertDatabaseCount('perfume_reviews',1);
    }
}
