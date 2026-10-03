<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Livestream;
use App\Models\Perfume;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeJournalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Video::query()->delete();
        Article::query()->delete();
    }

    public function test_stories_link_only_to_published_articles_with_a_working_fallback(): void
    {
        $article = Article::create(['title' => 'Nghi thức', 'slug' => 'xit-nuoc-hoa-o-dau', 'excerpt' => 'Giới thiệu', 'body' => 'Nội dung', 'is_published' => true]);
        Article::create(['title' => 'Bản nháp', 'slug' => 'hieu-ba-tang-huong', 'excerpt' => 'Giới thiệu', 'body' => 'Chưa công bố', 'is_published' => false]);
        $this->get('/')->assertOk()->assertSee('Mùi hương có hình.')
            ->assertSee(route('store.article', $article), false)
            ->assertDontSee('/cam-nang/hieu-ba-tang-huong', false)
            ->assertDontSee('id="video-soopi"', false);
        $this->get(route('store.article', $article))->assertOk();
        $this->get('/?gender=nu')->assertOk()->assertDontSee('id="nhat-ky-mui-huong"', false);
    }

    public function test_library_preserves_real_video_metadata_and_current_product_price(): void
    {
        $perfume = Perfume::create(['name' => 'Chai hương', 'slug' => 'chai-huong', 'brand' => 'Soopi',
            'gender' => 'nu', 'volume_ml' => 100, 'price' => 1500000, 'sale_price' => 1200000, 'stock' => 5, 'is_active' => true]);
        $video = Video::create(['title' => 'Video đang hiển thị', 'video_url' => '/storage/review.mp4',
            'placement' => 'home', 'is_active' => true, 'perfume_id' => $perfume->id, 'duration' => '0:37']);
        Video::create(['title' => 'Video đã ẩn', 'video_url' => '/storage/hidden.mp4', 'placement' => 'home', 'is_active' => false]);
        Video::create(['title' => 'Chỉ trang sản phẩm', 'video_url' => '/storage/product.mp4', 'placement' => 'product', 'is_active' => true]);
        Video::create(['title' => 'Không có nguồn', 'video_url' => '', 'placement' => 'home', 'is_active' => true]);
        $this->get('/')->assertOk()->assertSee('Video đang hiển thị')->assertSee('data-embed="'.asset('storage/review.mp4').'"', false)
            ->assertSee('data-perfume-price="1.200.000₫"', false)->assertSee('0:37')
            ->assertDontSee('Video đã ẩn')->assertDontSee('Chỉ trang sản phẩm')->assertDontSee('Không có nguồn')
            ->assertViewHas('homeVideos', fn ($items) => $items->modelKeys() === [$video->id]);
        $perfume->update(['is_active' => false]);
        $this->get('/')->assertOk()->assertSee('data-perfume-url=""', false)
            ->assertDontSee('data-perfume-price="1.200.000₫"', false);
    }

    public function test_live_call_to_action_requires_an_actual_broadcast(): void
    {
        $this->get('/')->assertOk()->assertSee('Xem lịch live')->assertDontSee('Vào xem trực tiếp');
        $live = Livestream::create(['title' => 'Buổi live', 'source' => 'browser', 'status' => 'live', 'last_heartbeat_at' => now()->subMinutes(2)]);
        $this->get('/')->assertOk()->assertSee('Xem lịch live')->assertDontSee('Vào xem trực tiếp');
        $live->update(['last_heartbeat_at' => now()]);
        $this->get('/')->assertOk()->assertSee('Vào xem trực tiếp');
    }
}
