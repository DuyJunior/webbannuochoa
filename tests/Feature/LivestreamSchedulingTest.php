<?php

namespace Tests\Feature;

use App\Models\Livestream;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivestreamSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_shortcut_preselects_the_schedule_option(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $this->actingAs($staff)->get(route('admin.livestreams.create', ['mode' => 'schedule']))
            ->assertOk()->assertSee('name="launch_mode" value="schedule" checked', false);
    }
    public function test_browser_livestream_now_opens_studio_without_claiming_it_is_on_air(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);

        $this->actingAs($staff)->post(route('admin.livestreams.store'), [
            'title' => 'Live ngay', 'source' => 'browser', 'launch_mode' => 'now', 'status' => 'scheduled',
        ])->assertRedirect(route('admin.livestreams.studio', 1));

        $stream = Livestream::firstOrFail();
        $this->assertSame('scheduled', $stream->status);
        $this->assertNull($stream->starts_at);
        $this->get(route('admin.livestreams.studio', $stream))->assertOk()->assertSee('Livestream ngay');
    }

    public function test_schedule_saves_vietnam_wall_time_and_returns_to_list(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $local = now('Asia/Ho_Chi_Minh')->addDay()->startOfMinute();

        $this->actingAs($staff)->post(route('admin.livestreams.store'), [
            'title' => 'Lịch ngày mai', 'source' => 'browser', 'launch_mode' => 'schedule',
            'status' => 'scheduled', 'starts_at' => $local->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('admin.livestreams.index'));

        $stream = Livestream::firstOrFail();
        $this->assertSame($local->format('Y-m-d H:i:00'), $stream->getRawOriginal('starts_at'));
        $this->get(route('admin.livestreams.edit', $stream))
            ->assertOk()->assertSee('value="'.$local->format('Y-m-d\TH:i').'"', false);
        $this->get(route('livestream.show'))->assertOk()->assertSee('Lịch ngày mai');
    }

    public function test_schedule_requires_a_future_time_in_vietnam(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $this->actingAs($staff)->post(route('admin.livestreams.store'), [
            'title' => 'Thiếu giờ', 'source' => 'browser', 'launch_mode' => 'schedule', 'status' => 'scheduled',
        ])->assertSessionHasErrors('starts_at');

        $this->post(route('admin.livestreams.store'), [
            'title' => 'Giờ đã qua', 'source' => 'browser', 'launch_mode' => 'schedule',
            'status' => 'scheduled',
            'starts_at' => now('Asia/Ho_Chi_Minh')->subMinute()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('livestreams', 0);
    }

    public function test_overdue_schedule_is_marked_in_admin_and_not_advertised_to_shoppers(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        Livestream::create([
            'title' => 'Lịch đã qua', 'source' => 'browser', 'status' => 'scheduled',
            'starts_at' => now('Asia/Ho_Chi_Minh')->subDay()->format('Y-m-d H:i:s'),
        ]);
        Livestream::create([
            'title' => 'Lịch sắp tới', 'source' => 'browser', 'status' => 'scheduled',
            'starts_at' => now('Asia/Ho_Chi_Minh')->addDay()->format('Y-m-d H:i:s'),
        ]);

        $this->actingAs($staff)->get(route('admin.livestreams.index'))
            ->assertOk()->assertSee('Đã tới giờ · chưa phát')->assertSee('Livestream ngay');
        $this->get(route('livestream.show'))
            ->assertOk()->assertSee('Lịch sắp tới')->assertDontSee('Lịch đã qua');
    }

    public function test_recently_due_schedule_waits_for_staff_instead_of_disappearing(): void
    {
        Livestream::create([
            'title' => 'Đang chuẩn bị', 'source' => 'browser', 'status' => 'scheduled',
            'starts_at' => now('Asia/Ho_Chi_Minh')->subMinutes(10)->format('Y-m-d H:i:s'),
        ]);
        Livestream::create([
            'title' => 'Ngày mai', 'source' => 'browser', 'status' => 'scheduled',
            'starts_at' => now('Asia/Ho_Chi_Minh')->addDay()->format('Y-m-d H:i:s'),
        ]);

        $this->get(route('livestream.show'))
            ->assertOk()->assertSee('Đang chuẩn bị')->assertSee('Đã đến giờ phát')->assertDontSee('Ngày mai');
    }
    public function test_youtube_livestream_now_still_appears_to_shoppers(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $this->actingAs($staff)->post(route('admin.livestreams.store'), [
            'title' => 'YouTube ngay', 'source' => 'youtube', 'launch_mode' => 'now',
            'status' => 'scheduled', 'youtube_url' => 'https://youtu.be/abcdefghijk',
        ])->assertRedirect(route('admin.livestreams.index'));

        $this->assertSame('live', Livestream::firstOrFail()->status);
        $this->get(route('livestream.show'))->assertOk()->assertSee('www.youtube-nocookie.com/embed/abcdefghijk');
    }
}
