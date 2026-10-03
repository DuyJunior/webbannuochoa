<?php

namespace Tests\Feature;

use App\Models\Perfume;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoPlaybackTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_media_urls_are_safe_and_local_videos_resolve_from_every_page(): void
    {
        foreach (['javascript:alert(1)', 'data:text/html,<script>alert(1)</script>', '//example.test/clip.mp4', '/storage/../secret.mp4'] as $url) {
            $this->assertNull((new Perfume(['video_url' => $url]))->embed_video_url);
            $this->assertSame('', (new Video(['video_url' => $url]))->embed_url);
        }
        foreach (['videos/review.mp4', '/storage/reviews/clip.webm'] as $url) {
            $expected = asset(ltrim($url, '/'));
            $this->assertSame($expected, (new Perfume(['video_url' => $url]))->embed_video_url);
            $this->assertSame($expected, (new Video(['video_url' => $url]))->embed_url);
        }
        $watch = 'https://www.youtube.com/watch?feature=share&v=abcdefghijk';
        $this->assertSame('https://www.youtube.com/embed/abcdefghijk?autoplay=1&rel=0', (new Video(['video_url' => $watch]))->embed_url);
    }

    public function test_product_player_respects_home_only_and_hidden_video_placement(): void
    {
        $this->withoutVite();
        $perfume = Perfume::create(['name' => 'Rose Test', 'slug' => 'rose-video-test', 'brand' => 'Soopi',
            'gender' => 'nu', 'price' => 1200000, 'volume_ml' => 100, 'stock' => 2, 'is_active' => true]);
        $video = Video::create(['perfume_id' => $perfume->id, 'title' => 'Video trên trang chủ',
            'video_url' => 'videos/review.mp4', 'placement' => 'home', 'is_active' => true]);
        $this->get(route('perfumes.show', $perfume))->assertOk()->assertDontSee('Xem video sản phẩm');
        $video->update(['placement' => 'product']);
        $this->get(route('perfumes.show', $perfume))->assertOk()->assertSee('Xem video sản phẩm')
            ->assertSee('data-embed="'.asset('videos/review.mp4').'"', false);
        $video->update(['is_active' => false]);
        $this->get(route('perfumes.show', $perfume))->assertOk()->assertDontSee('Xem video sản phẩm');
        $perfume->update(['video_url' => 'javascript:alert(1)']);
        $this->get(route('perfumes.show', $perfume))->assertOk()->assertDontSee('Xem video sản phẩm');
    }
}
