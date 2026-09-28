<?php

namespace Tests\Feature;

use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeluxeBoutiqueFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function createSamplePerfume(string $name = 'Chanel No 5'): Perfume
    {
        return Perfume::create([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'brand' => 'Chanel',
            'gender' => 'nu',
            'concentration' => 'EDP',
            'volume_ml' => 100,
            'price' => 3500000,
            'stock' => 10,
            'description' => 'Hương thơm kinh điển quý phái, ngọt ngào quyến rũ.',
            'is_active' => true,
        ]);
    }

    public function test_deluxe_public_routes_render_ok(): void
    {
        $perfume = $this->createSamplePerfume();

        // 1. Quiz chọn nước hoa
        $this->get(route('store.quiz'))
            ->assertOk()
            ->assertSee('TRẮC NGHIỆM CHỌN HƯƠNG');

        // Quiz có câu trả lời
        $this->get(route('store.quiz', [
            'personality' => 'charming',
            'weather' => 'cool',
            'occasion' => 'date',
            'note' => 'floral',
            'gender' => 'nu',
        ]))->assertOk()->assertSee('Soopi Đã Tìm Thấy Mùi Hương Hoàn Hảo Cho Bạn');

        // 2. Hộp thử mùi Discovery Box
        $this->get(route('store.discovery-box'))
            ->assertOk()
            ->assertSee('Hộp Thử Mùi')
            ->assertSee($perfume->name);

        // 3. Mùi hương hôm nay
        $this->get(route('store.scent-of-the-day'))
            ->assertOk()
            ->assertSee('MÙI HƯƠNG HÔM NAY')
            ->assertSee($perfume->name);

        // 4. So sánh nước hoa
        $this->get(route('store.compare', ['ids' => (string) $perfume->id]))
            ->assertOk()
            ->assertSee('BẢNG ĐỐI CHIẾU MÙI HƯƠNG')
            ->assertSee($perfume->name);

        // 5. Gửi quà cho bạn bè
        $this->get(route('store.gift-share', ['product' => $perfume->id, 'sender' => 'Hà Thu']))
            ->assertOk()
            ->assertSee('BẠN VỪA NHẬN ĐƯỢC MỘT MÓN QUÀ MÙI HƯƠNG')
            ->assertSee($perfume->name);
    }

    public function test_authenticated_user_can_use_wardrobe_and_member_page(): void
    {
        $user = User::factory()->create();
        $perfume = $this->createSamplePerfume();

        // Trang thành viên
        $this->actingAs($user)->get(route('store.member'))
            ->assertOk()
            ->assertSee('Đặc Quyền Thành Viên');

        // Thêm vào tủ nước hoa
        $this->actingAs($user)->post(route('store.wardrobe.add'), [
            'perfume_id' => $perfume->id,
            'occasion' => 'date',
            'notes' => 'Dành cho buổi tối lãng mạn',
        ])->assertRedirect();

        $this->assertDatabaseHas('scent_wardrobes', [
            'user_id' => $user->id,
            'perfume_id' => $perfume->id,
            'occasion' => 'date',
        ]);

        // Xem tủ nước hoa
        $this->actingAs($user)->get(route('store.wardrobe'))
            ->assertOk()
            ->assertSee('Tủ Nước Hoa Của')
            ->assertSee($perfume->name);

        // Chia sẻ tủ nước hoa công khai
        $this->get(route('store.wardrobe.share', $user->id))
            ->assertOk()
            ->assertSee('Ghé Thăm Tủ Nước Hoa Của')
            ->assertSee($perfume->name);
    }
}
