<?php

namespace Tests\Feature;

use App\Models\Livestream;
use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivestreamInteractionTest extends TestCase
{
    use RefreshDatabase;

    private function stream(): Livestream
    {
        return Livestream::create([
            'title' => 'Chọn hương cùng Ha Thu',
            'source' => 'browser',
            'status' => 'live',
            'last_heartbeat_at' => now(),
        ]);
    }

    private function perfume(): Perfume
    {
        return Perfume::create([
            'name' => 'Hoa hồng', 'slug' => 'hoa-hong', 'brand' => 'Ha Thu',
            'gender' => 'nu', 'volume_ml' => 100, 'price' => 300000,
            'stock' => 10, 'is_active' => true,
        ]);
    }

    public function test_guest_can_chat_during_live_and_staff_can_moderate(): void
    {
        $stream = $this->stream();
        $this->postJson(route('livestream.messages.send', $stream), ['body' => 'Mùi này giữ hương lâu không?'])->assertCreated();
        $this->postJson(route('livestream.messages.send', $stream), ['body' => str_repeat('a', 301)])->assertUnprocessable();
        $message = $this->getJson(route('livestream.messages', $stream))->assertOk()->json('messages.0');
        $this->assertSame('Mùi này giữ hương lâu không?', $message['body']);
        $this->assertStringStartsWith('Khách #', $message['display_name']);

        $customer = User::factory()->create(['role' => 'user']);
        $this->actingAs($customer)->deleteJson(route('admin.livestreams.messages.hide', [$stream, $message['id']]))->assertForbidden();
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $this->actingAs($staff)->deleteJson(route('admin.livestreams.messages.hide', [$stream, $message['id']]))->assertOk();
        $this->getJson(route('livestream.messages', $stream))->assertJsonCount(0, 'messages');
        $stream->update(['status' => 'ended']);
        $this->postJson(route('livestream.messages.send', $stream), ['body' => 'Tin mới'])->assertStatus(409);
    }

    public function test_presence_pin_and_product_clicks_appear_in_report(): void
    {
        $stream = $this->stream();
        $product = $this->perfume();
        $stream->products()->attach($product->id);
        $this->postJson(route('livestream.presence', $stream))->assertOk();
        $this->postJson(route('livestream.presence', $stream))->assertOk();
        $this->getJson(route('livestream.messages', $stream))->assertJsonPath('watching', 1);

        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $this->actingAs($staff)->patchJson(route('admin.livestreams.pin', $stream), ['perfume_id' => $product->id])->assertOk();
        $this->get(route('livestream.show'))->assertOk()->assertSee('Đang giới thiệu');
        $this->get(route('livestream.products.open', [$stream, $product]))->assertRedirect();
        $this->get(route('admin.livestreams.report', $stream))->assertOk()
            ->assertSee('Khách đã xem')->assertSee('Lượt mở sản phẩm')->assertSee('1 lượt mở');

        $this->deleteJson(route('admin.livestreams.products.destroy', [$stream, $product]))->assertOk();
        $this->assertNull($stream->fresh()->pinned_perfume_id);
    }
}
