<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Coupon;
use App\Models\Livestream;
use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_article_filters_are_combined_and_preserved_in_pagination(): void
    {
        for ($index = 0; $index < 21; $index++) {
            Article::create(['title' => 'Rose draft '.$index, 'slug' => 'rose-'.$index,
                'excerpt' => 'Rose guide', 'body' => str_repeat('Rose guide ', 8), 'is_published' => false]);
        }
        Article::create(['title' => 'Published rose', 'slug' => 'published-rose', 'excerpt' => 'Rose guide',
            'body' => str_repeat('Rose guide ', 8), 'is_published' => true]);

        $this->get(route('admin.articles.index', ['search' => 'Rose', 'status' => 'draft']))
            ->assertOk()->assertDontSee('Published rose')
            ->assertViewHas('articles', fn ($articles) => $articles->total() === 21
                && str_contains($articles->nextPageUrl(), 'search=Rose')
                && str_contains($articles->nextPageUrl(), 'status=draft'));
    }

    public function test_coupon_usage_matches_checkout_and_availability_is_visible(): void
    {
        $coupon = Coupon::create(['code' => 'LIMITED', 'type' => 'fixed', 'value' => 10000,
            'minimum_order' => 0, 'usage_limit' => 2, 'is_active' => true]);
        foreach (['pending', 'completed', 'cancelled'] as $status) {
            Order::create(['user_id' => auth()->id(), 'customer_name' => 'Khách', 'phone' => '0900000000',
                'address' => 'Hà Nội', 'total_price' => 100000, 'coupon_code' => $coupon->code, 'status' => $status]);
        }
        Coupon::create(['code' => 'EXPIRED', 'type' => 'percent', 'value' => 10,
            'minimum_order' => 0, 'is_active' => true, 'expires_at' => now()->subDay()]);
        Coupon::create(['code' => 'UPCOMING', 'type' => 'percent', 'value' => 10,
            'minimum_order' => 0, 'is_active' => true, 'starts_at' => now()->addDay()]);

        $this->get(route('admin.coupons.index'))->assertOk()
            ->assertSee('Hết lượt dùng')->assertSee('Đã hết hạn')->assertSee('Chưa tới ngày')
            ->assertViewHas('coupons', fn ($coupons) => (int) $coupons->firstWhere('code', 'LIMITED')->used_count === 2);
        $this->assertFalse($coupon->isAvailableFor(100000));
    }

    public function test_coupon_can_expire_without_an_explicit_start_date(): void
    {
        $this->post(route('admin.coupons.store'), ['code' => 'welcome', 'type' => 'fixed',
            'value' => 10000, 'minimum_order' => 0, 'expires_at' => now()->addDay()->format('Y-m-d\TH:i')])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('coupons', ['code' => 'WELCOME', 'starts_at' => null]);

        $this->post(route('admin.coupons.store'), ['code' => 'INVALID', 'type' => 'percent', 'value' => 10,
            'minimum_order' => 0, 'starts_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'expires_at' => now()->addDay()->format('Y-m-d\TH:i')])->assertSessionHasErrors('expires_at');
    }

    public function test_coupon_filters_find_only_matching_enabled_codes(): void
    {
        foreach ([['ROSE10', true], ['ROSE20', false], ['OTHER', true]] as [$code, $active]) {
            Coupon::create(['code' => $code, 'type' => 'percent', 'value' => 10, 'minimum_order' => 0, 'is_active' => $active]);
        }
        $this->get(route('admin.coupons.index', ['search' => 'rose', 'status' => 'active']))
            ->assertOk()->assertSee('ROSE10')->assertDontSee('ROSE20')->assertDontSee('OTHER');
    }

    public function test_video_rejects_executable_links_but_retains_local_files(): void
    {
        foreach (['javascript:alert(1)', 'data:text/html,test', ['invalid']] as $url) {
            $this->post(route('admin.videos.store'), ['title' => 'Unsafe video', 'video_url' => $url, 'placement' => 'home'])
                ->assertSessionHasErrors('video_url');
        }
        $this->post(route('admin.videos.store'), ['title' => 'Local review', 'video_url' => 'videos/review.mp4',
            'placement' => 'home', 'is_active' => 0])->assertRedirect(route('admin.videos.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('videos', ['title' => 'Local review', 'is_active' => false]);
    }

    public function test_unchecked_publication_fields_survive_validation_redirects(): void
    {
        $article = Article::create(['title' => 'Published guide', 'slug' => 'published-guide', 'excerpt' => 'Guide',
            'body' => str_repeat('Guide ', 12), 'is_published' => true]);
        $this->from(route('admin.articles.edit', $article))->put(route('admin.articles.update', $article), [
            'title' => '', 'excerpt' => 'Guide', 'body' => str_repeat('Guide ', 12), 'is_published' => 0,
        ])->assertSessionHasErrors('title');
        $response = $this->get(route('admin.articles.edit', $article))->assertOk();
        $this->assertMatchesRegularExpression('/<input\b(?=[^>]*id="isPublishedCheck")(?=[^>]*type="checkbox")(?![^>]*\bchecked\b)[^>]*>/', $response->getContent());

        $video = Video::create(['title' => 'Review', 'video_url' => 'videos/review.mp4', 'placement' => 'home', 'is_active' => true]);
        $this->from(route('admin.videos.edit', $video))->put(route('admin.videos.update', $video), [
            'title' => '', 'video_url' => $video->video_url, 'placement' => 'home', 'is_active' => 0,
        ])->assertSessionHasErrors('title');
        $response = $this->get(route('admin.videos.edit', $video))->assertOk();
        $this->assertMatchesRegularExpression('/<input\b(?=[^>]*id="is_active")(?=[^>]*type="checkbox")(?![^>]*\bchecked\b)[^>]*>/', $response->getContent());
    }

    public function test_staff_can_filter_livestreams_without_changing_summary_counts(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'livestream_staff']));
        Livestream::create(['title' => 'Rose tomorrow', 'source' => 'browser', 'status' => 'scheduled']);
        Livestream::create(['title' => 'Rose previous', 'source' => 'browser', 'status' => 'ended']);
        Livestream::create(['title' => 'Other tomorrow', 'source' => 'browser', 'status' => 'scheduled']);
        $this->get(route('admin.livestreams.index', ['search' => 'Rose', 'status' => 'scheduled']))
            ->assertOk()->assertSee('Rose tomorrow')->assertDontSee('Rose previous')->assertDontSee('Other tomorrow')
            ->assertViewHas('stats', fn ($stats) => $stats['scheduled'] === 2 && $stats['ended'] === 1);
    }

    public function test_deselected_livestream_products_stay_deselected_after_validation_failure(): void
    {
        $perfume = Perfume::create(['name' => 'Rose', 'slug' => 'rose', 'brand' => 'Soopi', 'gender' => 'nu',
            'volume_ml' => 100, 'price' => 100000, 'stock' => 5, 'is_active' => true]);
        $stream = Livestream::create(['title' => 'Rose live', 'source' => 'browser', 'status' => 'scheduled']);
        $stream->products()->attach($perfume);
        $this->from(route('admin.livestreams.edit', $stream))->put(route('admin.livestreams.update', $stream), [
            'title' => '', 'source' => 'browser', 'status' => 'scheduled',
        ])->assertSessionHasErrors('title');
        $this->get(route('admin.livestreams.edit', $stream))->assertOk()->assertSee('Đã chọn 0 / 50 sản phẩm');
    }
}
