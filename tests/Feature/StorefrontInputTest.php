<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Perfume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontInputTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function perfume(string $gender = 'nu'): Perfume
    {
        return Perfume::create([
            'name' => 'Mùi hương '.$gender, 'slug' => 'audit-'.$gender, 'brand' => 'Soopi', 'gender' => $gender,
            'concentration' => 'EDP', 'volume_ml' => 100, 'price' => 500001,
            'stock' => 5, 'is_active' => true, 'description' => 'Hoa cỏ nhẹ nhàng.',
        ]);
    }

    public function test_quiz_requires_four_answers_and_rejects_malformed_input(): void
    {
        $this->get('/quiz?personality=charming')->assertOk()->assertViewHas('hasResult', false);
        foreach (['personality', 'weather', 'occasion', 'note', 'gender'] as $field) {
            $this->getJson('/quiz?'.http_build_query([$field => ['unexpected']]))
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->getJson('/quiz?personality=unknown')->assertUnprocessable();
    }

    public function test_quiz_respects_gender_and_uses_verified_notes_without_fake_percentages(): void
    {
        $women = $this->perfume('nu');
        $unisex = $this->perfume('unisex');
        $this->perfume('nam');
        $this->get('/quiz?personality=charming&weather=cool&occasion=date&note=floral&gender=nu')
            ->assertOk()->assertViewHas('hasResult', true)
            ->assertViewHas('recommendations', fn ($items) => $items->modelKeys() === [$women->id, $unisex->id])
            ->assertSee('data-quick-view=', false)->assertDontSee('% Hòa Hợp')
            ->assertDontSee('Độ lưu:')->assertSee('Cùng Soopi tìm hiểu thêm về nốt hương');
    }

    public function test_gift_card_rejects_arrays_and_describes_the_purchase_honestly(): void
    {
        $perfume = $this->perfume();
        $this->getJson('/tang-qua?from[]=invalid')->assertUnprocessable()->assertJsonValidationErrors('from');
        $this->get('/tang-qua?'.http_build_query(['id' => $perfume->id, 'from' => 'Linh', 'to' => 'Mai']))
            ->assertOk()->assertSee('Linh')->assertSee('Mai')
            ->assertSee('chưa phải đơn hàng đã thanh toán')->assertSee('+50.000₫');
    }

    public function test_daily_offer_uses_current_coupon_rules_and_exact_checkout_rounding(): void
    {
        $this->perfume();
        Coupon::where('code', 'TODAY10')->delete();
        $this->get('/mui-huong-hom-nay')->assertOk()->assertViewHas('todayCode', null)
            ->assertViewHas('dealPrice', 500001)->assertDontSee('id="copySotdBtn"', false);
        $coupon = Coupon::create(['code' => 'TODAY10', 'type' => 'percent', 'value' => 10,
            'minimum_order' => 0, 'is_active' => true]);
        $this->get('/mui-huong-hom-nay')->assertOk()->assertViewHas('todayCode', 'TODAY10')
            ->assertViewHas('dealPrice', 450001);
        $coupon->update(['minimum_order' => 1000000]);
        $this->get('/mui-huong-hom-nay')->assertOk()->assertViewHas('todayCode', null);
        $coupon->update(['minimum_order' => 0, 'expires_at' => now()->subDay()]);
        $this->get('/mui-huong-hom-nay')->assertOk()->assertViewHas('todayCode', null);
    }
}
