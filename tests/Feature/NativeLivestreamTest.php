<?php

namespace Tests\Feature;

use App\Models\Livestream;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NativeLivestreamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.livekit.url', 'wss://video.example.test');
        config()->set('services.livekit.key', 'test-key');
        config()->set('services.livekit.secret', 'test-secret');
    }

    public function test_home_links_to_livestream_and_announces_an_active_broadcast(): void
    {
        $stream = Livestream::create([
            'title' => 'Buổi phát thử trên trang chủ',
            'source' => 'browser',
            'status' => 'live',
            'last_heartbeat_at' => now(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('livestream.show').'"', false)
            ->assertSee('Buổi phát thử trên trang chủ');

        $stream->update(['status' => 'ended', 'last_heartbeat_at' => null]);
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Buổi phát thử trên trang chủ');
    }

    public function test_staff_can_create_browser_broadcast_without_youtube_url_and_open_studio(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $this->actingAs($staff)->post(route('admin.livestreams.store'), [
            'title' => 'Chuyện hương tối nay', 'source' => 'browser', 'status' => 'scheduled',
        ])->assertRedirect();

        $stream = Livestream::firstOrFail();
        $this->assertNull($stream->youtube_video_id);
        $this->get(route('admin.livestreams.studio', $stream))
            ->assertOk()->assertSee('Bật camera')->assertSee('livekit-client.umd.js');
        $this->get(route('admin.livestreams.index'))->assertOk()->assertSee('Livestream ngay');
    }

    public function test_customer_cannot_get_publish_token_but_can_get_subscribe_only_token_when_live(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $customer = User::factory()->create(['role' => 'user']);
        $stream = Livestream::create(['title' => 'Live trên web', 'source' => 'browser', 'status' => 'scheduled']);

        $this->actingAs($customer)->postJson(route('admin.livestreams.host-token', $stream))->assertForbidden();
        $this->postJson(route('livestream.viewer-token', $stream))->assertStatus(409);

        $this->actingAs($staff);
        $hostToken = $this->postJson(route('admin.livestreams.host-token', $stream))->assertOk()->json('participant_token');
        $hostGrants = $this->claims($hostToken)['video'];
        $this->assertTrue($hostGrants['canPublish']);
        $this->assertSame(['camera', 'microphone'], $hostGrants['canPublishSources']);

        $this->postJson(route('admin.livestreams.begin', $stream))->assertOk();
        $this->get(route('livestream.show'))->assertOk()->assertSee('Xem livestream');
        $viewerToken = $this->postJson(route('livestream.viewer-token', $stream))->assertOk()->json('participant_token');
        $viewerGrants = $this->claims($viewerToken)['video'];
        $this->assertFalse($viewerGrants['canPublish']);
        $this->assertTrue($viewerGrants['canSubscribe']);

        $this->postJson(route('admin.livestreams.finish', $stream))->assertOk();
        $this->postJson(route('livestream.viewer-token', $stream))->assertStatus(409);
    }

    public function test_stale_broadcast_stops_issuing_viewer_tokens_and_browser_cannot_be_marked_live_manually(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $stream = Livestream::create([
            'title' => 'Live gián đoạn', 'source' => 'browser', 'status' => 'live',
            'presenter_id' => $staff->id, 'last_heartbeat_at' => now()->subMinute(),
        ]);

        $this->get(route('livestream.show'))->assertOk()->assertSee('Tạm gián đoạn');
        $this->postJson(route('livestream.viewer-token', $stream))->assertStatus(409);
        $this->actingAs($staff)->patch(route('admin.livestreams.status', $stream), ['status' => 'live'])
            ->assertSessionHasErrors('status');
    }

    public function test_unconfigured_video_server_does_not_mark_broadcast_live(): void
    {
        config()->set('services.livekit.url', null);
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $stream = Livestream::create(['title' => 'Chưa có máy chủ video', 'source' => 'browser', 'status' => 'scheduled']);

        $this->actingAs($staff)->get(route('admin.livestreams.studio', $stream))
            ->assertOk()->assertDontSee('livekit-client.umd.js');
        $this->postJson(route('admin.livestreams.host-token', $stream))->assertStatus(503);
        $this->postJson(route('admin.livestreams.begin', $stream))->assertStatus(503);
        $this->assertSame('scheduled', $stream->fresh()->status);
    }
    public function test_public_state_updates_as_a_browser_broadcast_starts_and_ends(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $stream = Livestream::create(['title' => 'Buổi phát', 'source' => 'browser', 'status' => 'scheduled']);

        $this->getJson(route('livestream.state'))
            ->assertOk()->assertJson(['livestream_id' => $stream->id, 'on_air' => false]);

        $this->actingAs($staff)->postJson(route('admin.livestreams.begin', $stream))->assertOk();
        $this->getJson(route('livestream.state'))
            ->assertOk()->assertJson(['livestream_id' => $stream->id, 'on_air' => true]);

        $this->postJson(route('admin.livestreams.finish', $stream))->assertOk();
        $this->getJson(route('livestream.state'))
            ->assertOk()->assertJson(['livestream_id' => null, 'on_air' => false]);
    }

    public function test_admin_ending_a_broadcast_invalidates_the_presenter_heartbeat(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $admin = User::factory()->create(['role' => 'admin']);
        $stream = Livestream::create([
            'title' => 'Đang live', 'source' => 'browser', 'status' => 'live',
            'presenter_id' => $staff->id, 'last_heartbeat_at' => now(),
        ]);

        $this->actingAs($admin)->patch(route('admin.livestreams.status', $stream), ['status' => 'ended'])
            ->assertRedirect(route('admin.livestreams.index'));
        $this->actingAs($staff)->postJson(route('admin.livestreams.heartbeat', $stream))->assertStatus(409);
        $this->assertNull($stream->fresh()->presenter_id);
    }

    public function test_shared_ip_can_issue_viewer_tokens_for_dozen_of_customers(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $stream = Livestream::create([
            'title' => 'Đang live', 'source' => 'browser', 'status' => 'live',
            'presenter_id' => $staff->id, 'last_heartbeat_at' => now(),
        ]);

        for ($i = 0; $i < 35; $i++) {
            $this->postJson(route('livestream.viewer-token', $stream))->assertOk();
        }
    }
    public function test_livestream_page_omits_floating_sales_widgets(): void
    {
        $this->get(route('livestream.show'))
            ->assertOk()
            ->assertDontSee('ht-spin-floating-btn')
            ->assertDontSee('ht-social-float')
            ->assertSee('livestream-state.js');
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('ht-spin-floating-btn')
            ->assertSee('ht-social-float');
    }
    private function claims(string $token): array
    {
        $payload = explode('.', $token)[1];
        return json_decode(base64_decode(strtr($payload, '-_', '+/')), true, flags: JSON_THROW_ON_ERROR);
    }
}
