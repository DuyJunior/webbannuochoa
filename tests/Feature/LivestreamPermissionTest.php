<?php

namespace Tests\Feature;

use App\Models\Livestream;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivestreamPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_and_livestream_staff_can_manage_broadcasts(): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($customer)->get(route('home'))->assertOk()->assertDontSee('Quản lý livestream');
        $this->actingAs($customer)->get(route('admin.livestreams.index'))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.livestreams.store'), [])->assertForbidden();

        $this->actingAs($staff)->get(route('home'))->assertOk()->assertSee('Quản lý livestream');
        $this->actingAs($staff)->get(route('admin.livestreams.index'))->assertOk()->assertSee('Livestream');
        $this->actingAs($staff)->get(route('admin.users.index'))->assertRedirect(route('home'));
        $this->actingAs($staff)->get(route('admin.dashboard'))->assertRedirect(route('home'));

        $this->actingAs($admin)->get(route('admin.livestreams.index'))->assertOk();
    }

    public function test_staff_can_publish_a_youtube_livestream_and_customer_can_watch(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);

        $this->actingAs($staff)->post(route('admin.livestreams.store'), [
            'title' => 'Thử hương cùng Ha Thu',
            'youtube_url' => 'https://www.youtube.com/live/abcdefghijk',
            'status' => 'live',
        ])->assertRedirect(route('admin.livestreams.index'));

        $this->assertDatabaseHas('livestreams', [
            'title' => 'Thử hương cùng Ha Thu',
            'youtube_video_id' => 'abcdefghijk',
            'created_by' => $staff->id,
        ]);
        $this->get(route('livestream.show'))->assertOk()->assertSee('www.youtube-nocookie.com/embed/abcdefghijk');
    }

    public function test_invalid_video_link_is_rejected_and_only_one_stream_can_be_live(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);

        $this->actingAs($staff)->post(route('admin.livestreams.store'), [
            'title' => 'Không hợp lệ', 'youtube_url' => 'https://evil.example/watch?v=abcdefghijk', 'status' => 'live',
        ])->assertSessionHasErrors('youtube_url');

        Livestream::create(['title' => 'Đang live', 'youtube_video_id' => 'abcdefghijk', 'status' => 'live']);
        $this->actingAs($staff)->post(route('admin.livestreams.store'), [
            'title' => 'Live thứ hai', 'youtube_url' => 'https://youtu.be/123456789ab', 'status' => 'live',
        ])->assertSessionHasErrors('status');
    }

    public function test_staff_can_start_and_end_scheduled_stream_from_list(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $stream = Livestream::create([
            'title' => 'Hương tối nay',
            'youtube_video_id' => 'abcdefghijk',
            'status' => 'scheduled',
        ]);

        $this->actingAs($staff)->get(route('admin.livestreams.index'))->assertOk()->assertSee('Phát ngay');
        $this->patch(route('admin.livestreams.status', $stream), ['status' => 'live'])
            ->assertRedirect(route('admin.livestreams.index'));
        $this->assertSame('live', $stream->fresh()->status);
        $this->get(route('livestream.show'))->assertOk()->assertSee('Phát trực tiếp: Hương tối nay');

        $this->patch(route('admin.livestreams.status', $stream), ['status' => 'ended'])
            ->assertRedirect(route('admin.livestreams.index'));
        $this->assertSame('ended', $stream->fresh()->status);
        $this->get(route('livestream.show'))->assertOk()->assertSee('Hẹn bạn ở buổi phát tiếp theo');
    }
    public function test_livestream_staff_login_opens_only_their_work_area(): void
    {
        $staff = User::factory()->create([
            'role' => 'livestream_staff',
            'password' => bcrypt('secret123'),
        ]);

        $this->post(route('admin.login.post'), [
            'email' => $staff->email,
            'password' => 'secret123',
        ])->assertRedirect(route('admin.livestreams.index'));

        $this->assertAuthenticatedAs($staff);
    }
    public function test_admin_can_grant_and_revoke_livestream_staff_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)->put(route('admin.users.update', $customer), [
            'name' => $customer->name, 'email' => $customer->email, 'role' => 'livestream_staff',
        ])->assertRedirect(route('admin.users.index'));
        $this->assertSame('livestream_staff', $customer->fresh()->role);

        $customer = $customer->fresh();
        $this->actingAs($customer)->get(route('admin.livestreams.index'))->assertOk();
        $customer->update(['role' => 'user']);
        $this->actingAs($customer)->get(route('admin.livestreams.index'))->assertForbidden();
    }
}
