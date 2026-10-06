<?php

namespace Tests\Feature;

use App\Models\GiftExperience;
use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GiftExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! getenv('SOOPI_GIFT_EXPORT')) {
            $this->withoutVite();
        }
        Storage::fake('gifts');
    }

    private function payload(array $extra = []): array
    {
        return array_merge(['sender_name' => 'Minh', 'recipient_name' => 'Ngọc Linh', 'message' => 'Chúc bạn một ngày thật dịu dàng.', 'pin' => '012345', 'days' => 90], $extra);
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('memory.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aNioAAAAASUVORK5CYII='));
    }

    private function createGift(array $extra = []): GiftExperience
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('gifts.store'), $this->payload($extra))->assertSessionHasNoErrors()->assertRedirect();

        return GiftExperience::latest('id')->firstOrFail();
    }

    private function guest(): void
    {
        auth()->forgetGuards();
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }

    public function test_guest_and_unverified_users_cannot_create_gifts(): void
    {
        $this->get(route('gifts.create'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->unverified()->create())->post(route('gifts.store'), $this->payload())->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('gift_experiences', 0);
    }

    public function test_create_private_gift_unlock_and_thank_once(): void
    {
        $gift = $this->createGift(['photo' => $this->photo()]);
        $this->assertTrue(Hash::check('012345', $gift->pin_hash));
        $this->assertNotSame($gift->message, DB::table('gift_experiences')->value('message'));
        Storage::disk('gifts')->assertExists($gift->photo_path);
        $this->get(route('gifts.edit', $gift))->assertOk()->assertSee('Tải QR')->assertDontSee('012345');
        $this->guest();
        $this->get(route('gifts.open', $gift->token))->assertOk()->assertDontSee($gift->message)->assertDontSee($gift->recipient_name)->assertDontSee($gift->photo_path)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')->assertHeader('Referrer-Policy', 'no-referrer');
        $this->get(route('gifts.media', [$gift->token, 'photo']))->assertForbidden();
        $this->post(route('gifts.thank', $gift->token), ['thank_you' => 'Cảm ơn nhé!'])->assertForbidden();
        $this->post(route('gifts.unlock', $gift->token), ['pin' => '999999'])->assertSessionHasErrors('pin');
        $this->post(route('gifts.unlock', $gift->token), ['pin' => '012345'])->assertRedirect(route('gifts.open', $gift->token));
        $this->get(route('gifts.open', $gift->token))->assertOk()->assertSee($gift->message);
        $this->get(route('gifts.media', [$gift->token, 'photo']))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertNotNull($gift->fresh()->opened_at);
        $this->post(route('gifts.thank', $gift->token), ['thank_you' => 'Cảm ơn, mình rất thích!'])->assertRedirect();
        $this->post(route('gifts.thank', $gift->token), ['thank_you' => 'Overwrite'])->assertRedirect();
        $this->assertSame('Cảm ơn, mình rất thích!', $gift->fresh()->thank_you);
        $this->get(route('gifts.open', $gift->token))->assertSee('Lời cảm ơn đã được gửi.')->assertDontSee('Cảm ơn, mình rất thích!');
        $this->post(route('gifts.lock', $gift->token))->assertRedirect();
        $this->get(route('gifts.media', [$gift->token, 'photo']))->assertForbidden();
        $this->actingAs(User::find($gift->user_id))->get(route('gifts.edit', $gift))->assertSee('Cảm ơn, mình rất thích!');
    }

    public function test_only_owner_can_manage_preview_or_delete_and_old_media_is_removed(): void
    {
        $gift = $this->createGift(['photo' => $this->photo()]);
        $owner = User::find($gift->user_id);
        $this->actingAs(User::factory()->create());
        foreach (['gifts.edit', 'gifts.preview'] as $route) {
            $this->get(route($route, $gift))->assertNotFound();
        }
        $this->put(route('gifts.update', $gift), $this->payload())->assertNotFound();
        $this->delete(route('gifts.destroy', $gift))->assertNotFound();
        $this->get(route('gifts.index'))->assertDontSee($gift->recipient_name);
        $this->actingAs($owner);
        $this->get(route('gifts.preview', $gift))->assertOk();
        $this->assertNull($gift->fresh()->opened_at);
        $first = $gift->photo_path;
        $this->put(route('gifts.update', $gift), $this->payload(['pin' => '', 'days' => '', 'photo' => $this->photo()]))->assertSessionHasNoErrors();
        Storage::disk('gifts')->assertMissing($first);
        $next = $gift->fresh()->photo_path;
        Storage::disk('gifts')->assertExists($next);
        $this->delete(route('gifts.destroy', $gift))->assertRedirect(route('gifts.index'));
        Storage::disk('gifts')->assertMissing($next);
        $this->get(route('gifts.open', $gift->token))->assertNotFound();
        $this->get(route('gifts.media', [$gift->token, 'photo']))->assertNotFound();
    }

    public function test_pin_change_expiry_and_session_timeout_revoke_access(): void
    {
        $gift = $this->createGift();
        $this->post(route('gifts.unlock', $gift->token), ['pin' => '012345']);
        $this->put(route('gifts.update', $gift), $this->payload(['pin' => '654321', 'days' => '']))->assertSessionHasNoErrors();
        $this->get(route('gifts.open', $gift->token))->assertDontSee($gift->message);
        $this->post(route('gifts.unlock', $gift->token), ['pin' => '012345'])->assertSessionHasErrors('pin');
        $this->post(route('gifts.unlock', $gift->token), ['pin' => '654321'])->assertSessionHasNoErrors();
        $this->travel(61)->minutes();
        $this->get(route('gifts.open', $gift->token))->assertDontSee($gift->message);
        $this->travel(91)->days();
        $this->get(route('gifts.open', $gift->token))->assertStatus(410);
        $this->post(route('gifts.unlock', $gift->token), ['pin' => '654321'])->assertStatus(410);
    }

    public function test_validation_blocks_unowned_orders_nonmatching_products_and_unsafe_files(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = Perfume::create(['name' => 'Rose', 'slug' => 'rose', 'brand' => 'Soopi', 'gender' => 'nu', 'price' => 1000000, 'stock' => 5, 'volume_ml' => 100, 'is_active' => true]);
        $order = Order::create(['user_id' => $other->id, 'customer_name' => 'Other', 'phone' => '0900000000', 'address' => 'Hà Nội', 'status' => 'pending', 'total_price' => 1000000]);
        $this->actingAs($owner);
        $this->post(route('gifts.store'), $this->payload(['order_id' => $order->id]))->assertSessionHasErrors('order_id');
        $this->get(route('gifts.create', ['order_id' => $order->id]))->assertNotFound();
        $order->update(['user_id' => $owner->id]);
        $this->post(route('gifts.store'), $this->payload(['order_id' => $order->id, 'perfume_id' => $product->id]))->assertSessionHasErrors('perfume_id');
        $this->post(route('gifts.store'), $this->payload(['photo' => UploadedFile::fake()->create('script.svg', 5, 'image/svg+xml')]))->assertSessionHasErrors('photo');
        $this->post(route('gifts.store'), $this->payload(['audio' => UploadedFile::fake()->create('script.html', 5, 'text/html')]))->assertSessionHasErrors('audio');
        $this->post(route('gifts.store'), $this->payload(['pin' => '12', 'days' => 999]))->assertSessionHasErrors(['pin', 'days']);
        $this->assertDatabaseCount('gift_experiences', 0);
        $order->items()->create(['perfume_id' => $product->id, 'quantity' => 1, 'price' => 1000000]);
        $this->post(route('gifts.store'), $this->payload(['order_id' => $order->id, 'perfume_id' => $product->id]))->assertSessionHasNoErrors();
        $this->get(route('orders.show', $order))->assertSee('Tạo thiệp quà QR');
    }

    public function test_pin_guessing_is_limited_even_for_correct_code_after_limit(): void
    {
        $gift = $this->createGift();
        $this->guest();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('gifts.unlock', $gift->token), ['pin' => '999999'])->assertSessionHasErrors('pin');
        }
        $this->post(route('gifts.unlock', $gift->token), ['pin' => '012345'])->assertSessionHasErrors('pin');
        $this->get(route('gifts.open', $gift->token))->assertDontSee($gift->message);
        $this->travel(11)->minutes();
        $this->post(route('gifts.unlock', $gift->token), ['pin' => '012345'])->assertSessionHasNoErrors();
    }

    public function test_bilingual_pages_render_and_can_export_isolated_previews(): void
    {
        $perfume = Perfume::create(['name' => 'Miss Dior Blooming Bouquet', 'slug' => 'gift-rose', 'brand' => 'Dior', 'gender' => 'nu',
            'price' => 1200000, 'stock' => 5, 'volume_ml' => 100, 'is_active' => true, 'image_url' => 'images/gallery/miss-dior.webp',
            'description' => 'Một nét hương hoa dịu dàng, dành cho những khoảnh khắc muốn giữ lại.',
            'description_en' => 'A soft floral fragrance for the moments you want to keep.']);
        $gift = $this->createGift(['photo' => $this->photo(), 'perfume_id' => $perfume->id]);
        foreach (['vi', 'en'] as $locale) {
            $this->withSession(['locale' => $locale]);
            foreach (['index', 'create', 'edit', 'preview', 'card'] as $view) {
                $response = $this->get(route('gifts.'.$view, in_array($view, ['edit', 'preview', 'card']) ? $gift : []))->assertOk();
                if ($locale === 'en') {
                    $response->assertDontSee('Tạo trang quà')->assertDontSee('Lời nhắn của bạn')->assertDontSee('Quay lại chỉnh sửa');
                }
                if (getenv('SOOPI_GIFT_EXPORT')) {
                    file_put_contents(storage_path('app/gift-'.$view.'-'.$locale.'.html'), $response->getContent());
                }
            }
            $response = $this->get(route('gifts.open', $gift->token))->assertOk();
            if (getenv('SOOPI_GIFT_EXPORT')) {
                file_put_contents(storage_path('app/gift-locked-'.$locale.'.html'), $response->getContent());
            }
            $response = $this->withSession(['gift_access.'.$gift->id => ['version' => $gift->access_version, 'until' => now()->addHour()->timestamp]])
                ->get(route('gifts.open', $gift->token))->assertOk()->assertSee('Miss Dior Blooming Bouquet')->assertDontSee('1.200.000');
            if ($locale === 'en') {
                $response->assertSee('Send a little love back.')->assertSee('A soft floral fragrance');
            }
            if (getenv('SOOPI_GIFT_EXPORT')) {
                file_put_contents(storage_path('app/gift-open-'.$locale.'.html'), $response->getContent());
            }
            $this->session(['gift_access' => []]);
        }
    }

    public function test_audio_remains_private_and_is_removed_with_the_gift(): void
    {
        $wav = 'RIFF'.pack('V', 36 + 8000).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 8000, 1, 8).'data'.pack('V', 8000).str_repeat(chr(128), 8000);
        $gift = $this->createGift(['audio' => UploadedFile::fake()->createWithContent('greeting.wav', $wav)]);
        Storage::disk('gifts')->assertExists($gift->audio_path);
        $this->get(route('gifts.media', [$gift->token, 'audio']))->assertOk();
        $this->guest();
        $this->get(route('gifts.media', [$gift->token, 'audio']))->assertForbidden();
        $this->post(route('gifts.unlock', $gift->token), ['pin' => '012345'])->assertSessionHasNoErrors();
        $this->get(route('gifts.media', [$gift->token, 'audio']))->assertOk();
        $this->actingAs(User::find($gift->user_id))->delete(route('gifts.destroy', $gift))->assertRedirect();
        Storage::disk('gifts')->assertMissing($gift->audio_path);
    }

    public function test_content_is_escaped_and_pin_is_not_flashed_on_validation_error(): void
    {
        $gift = $this->createGift(['message' => '<script>alert(1)</script>']);
        $this->get(route('gifts.preview', $gift))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->post(route('gifts.store'), $this->payload(['recipient_name' => '']))->assertSessionHasErrors('recipient_name')->assertSessionMissing('_old_input.pin');
    }

    public function test_page_quota_and_removing_media_without_replacing_it(): void
    {
        $gift = $this->createGift(['photo' => $this->photo()]);
        $this->put(route('gifts.update', $gift), $this->payload(['remove_photo' => 1]))->assertSessionHasNoErrors();
        Storage::disk('gifts')->assertMissing($gift->photo_path);
        $this->assertNull($gift->fresh()->photo_path);
        for ($i = 0; $i < 19; $i++) {
            $copy = $gift->fresh()->replicate();
            $copy->token = Str::random(48);
            $copy->save();
        }
        $this->post(route('gifts.store'), $this->payload())->assertSessionHasErrors('message');
        $this->assertDatabaseCount('gift_experiences', 20);
    }
}
