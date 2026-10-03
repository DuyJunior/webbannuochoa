<?php

namespace Tests\Feature;

use App\Models\Perfume;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_existing_video_copy_changes_on_home_product_and_modal_without_overwriting_vietnamese(): void
    {
        $perfume = Perfume::create(['name' => 'Miss Dior', 'slug' => 'video-language', 'brand' => 'Dior',
            'gender' => 'nu', 'price' => 1000000, 'volume_ml' => 100, 'stock' => 2, 'is_active' => true]);
        $video = Video::create(['title' => 'Miss Dior: Hương hoa hồng ngọt dịu đầu mùa',
            'description' => 'Khám phá nốt hương hoa mẫu đơn dịu dàng và hoa hồng Grasse kiêu sa cùng độ tỏa hương mềm mại suốt ngày dài.',
            'perfume_id' => $perfume->id, 'video_url' => 'videos/test.mp4', 'placement' => 'all', 'is_active' => true]);
        foreach (['/', '/perfumes/'.$perfume->id] as $url) {
            $this->withSession(['locale' => 'en'])->get($url)->assertOk()
                ->assertSee('Miss Dior: Soft, Sweet Roses of Early Spring')
                ->assertSee('data-desc="Discover gentle peony', false)
                ->assertSee('View &amp; Shop', false)->assertDontSee($video->title);
            $this->withSession(['locale' => 'vi'])->get($url)->assertOk()->assertSee($video->title);
        }
        $this->assertDatabaseHas('videos', ['id' => $video->id, 'title' => $video->title, 'title_en' => null]);
    }

    public function test_admin_can_save_search_and_clear_english_copy_independently(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = ['title' => 'Câu chuyện mới', 'title_en' => 'An English fragrance story',
            'description' => 'Mô tả tiếng Việt', 'description_en' => 'An English description',
            'video_url' => 'videos/test.mp4', 'placement' => 'home', 'is_active' => 1];
        $this->actingAs($admin)->post('/admin/videos', $payload)->assertSessionHasNoErrors()->assertRedirect();
        $video = Video::where('title', $payload['title'])->sole();
        $this->assertSame($payload['title_en'], $video->title_en);
        $this->withSession(['locale' => 'en'])->get('/admin/videos?search=English')->assertOk()
            ->assertSee($payload['title_en'])->assertViewHas('videos', fn ($videos) => $videos->count() === 1);
        $this->get('/admin/videos/'.$video->id.'/edit')->assertSee('English title')->assertSee('Câu chuyện mới');
        $this->put('/admin/videos/'.$video->id, array_merge($payload, ['title_en' => '', 'description_en' => '']))
            ->assertSessionHasNoErrors();
        $video->refresh();
        $this->assertNull($video->title_en);
        $this->assertSame('Câu chuyện mới', $video->localized_title);
        $this->postJson('/admin/videos', array_merge($payload, ['title_en' => str_repeat('x', 256), 'description_en' => ['invalid']]))
            ->assertUnprocessable()->assertJsonValidationErrors(['title_en', 'description_en']);
    }

    public function test_manual_english_copy_has_priority_is_escaped_and_does_not_affect_vietnamese(): void
    {
        $video = Video::create(['title' => 'EM ĐẸP NHƯ TIÊN', 'title_en' => '<img src=x onerror=alert(1)>',
            'description_en' => 'A "quoted" description', 'video_url' => 'videos/test.mp4', 'placement' => 'home', 'is_active' => true]);
        $this->withSession(['locale' => 'en'])->get('/')->assertOk()
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)->assertDontSee('<img src=x onerror=alert(1)>', false);
        $this->withSession(['locale' => 'vi'])->get('/')->assertSee('EM ĐẸP NHƯ TIÊN')->assertDontSee('onerror=alert(1)');
        $this->assertSame('EM ĐẸP NHƯ TIÊN', $video->fresh()->title);
    }
}
