<?php

namespace Tests\Feature;

use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeVideoLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_keeps_all_published_videos_in_admin_order_including_the_fifth_and_later(): void
    {
        $this->withoutVite();
        Video::query()->delete(); // Isolate the library from legacy migration seed videos.
        $videos = collect(range(1, 7))->map(fn ($number) => Video::create([
            'title' => 'Library video '.$number, 'video_url' => 'https://www.youtube.com/watch?v=kYv9bH8F688',
            'thumbnail_url' => 'images/perfume-default.jpg', 'placement' => $number % 2 ? 'home' : 'all',
            'sort_order' => $number, 'is_active' => true,
        ]));
        Video::create(['title' => 'Hidden video', 'video_url' => 'https://www.youtube.com/watch?v=kYv9bH8F688', 'placement' => 'home', 'is_active' => false]);
        Video::create(['title' => 'Product only video', 'video_url' => 'https://www.youtube.com/watch?v=kYv9bH8F688', 'placement' => 'product', 'is_active' => true]);
        Video::create(['title' => 'Missing URL', 'video_url' => '', 'placement' => 'home', 'is_active' => true]);
        $this->get('/')->assertOk()->assertViewHas('homeVideos', fn ($items) => $items->modelKeys() === $videos->pluck('id')->all())
            ->assertSeeInOrder($videos->pluck('title')->all())->assertSee('data-video-next', false)
            ->assertDontSee('Hidden video')->assertDontSee('Product only video')->assertDontSee('Missing URL');
        $videos->last()->update(['sort_order' => 0]);
        $this->get('/')->assertViewHas('homeVideos', fn ($items) => $items->count() === 7 && $items->first()->id === $videos->last()->id);
        $this->get('/?search=none')->assertOk()->assertDontSee('data-video-rail', false);
        if (getenv('SOOPI_VIDEO_RAIL_EXPORT') === '1') {
            $this->withVite();
            file_put_contents(storage_path('app/video-rail-fixture.html'), $this->get('/')->assertOk()->getContent());
        }
    }
}
