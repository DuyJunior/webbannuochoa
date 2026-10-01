<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PerfumeCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_product_list_can_be_viewed(): void
    {
        Perfume::create($this->perfumeData());

        $response = $this->get(route('perfumes.index'));

        $response->assertOk()->assertSee('Bleu de Chanel');
    }

    public function test_product_can_be_created(): void
    {
        $category = Category::create(['name' => 'Nước hoa nam']);
        $payload = $this->perfumeData(['category_id' => $category->id]);
        unset($payload['slug']);

        $response = $this->post(route('perfumes.store'), $payload);

        Perfume::firstOrFail();
        $response->assertRedirect(route('perfumes.index'));
        $this->assertDatabaseHas('perfumes', [
            'name' => 'Bleu de Chanel',
            'slug' => 'bleu-de-chanel',
            'brand' => 'Chanel',
        ]);
    }

    public function test_product_can_be_updated(): void
    {
        $perfume = Perfume::create($this->perfumeData());
        $payload = $this->perfumeData([
            'name' => 'Bleu de Chanel Parfum',
            'price' => 4200000,
            'stock' => 8,
        ]);
        unset($payload['slug']);

        $response = $this->put(route('perfumes.update', $perfume), $payload);

        $response->assertRedirect(route('perfumes.show', $perfume));
        $this->assertDatabaseHas('perfumes', [
            'id' => $perfume->id,
            'name' => 'Bleu de Chanel Parfum',
            'slug' => 'bleu-de-chanel-parfum',
            'stock' => 8,
        ]);
    }

    public function test_product_can_be_deleted(): void
    {
        $perfume = Perfume::create($this->perfumeData());

        $response = $this->delete(route('perfumes.destroy', $perfume));

        $response->assertRedirect(route('perfumes.index'));
        $this->assertSoftDeleted('perfumes', ['id' => $perfume->id]);
    }

    public function test_sale_price_cannot_exceed_list_price(): void
    {
        $payload = $this->perfumeData(['price' => 1000000, 'sale_price' => 1200000]);
        unset($payload['slug']);

        $response = $this->post(route('perfumes.store'), $payload);

        $response->assertSessionHasErrors('sale_price');
        $this->assertDatabaseCount('perfumes', 0);
    }

    public function test_product_image_can_be_uploaded_from_computer(): void
    {
        $payload = $this->perfumeData();
        unset($payload['slug'], $payload['image_url']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $payload['image_file'] = UploadedFile::fake()->createWithContent('nuoc-hoa.png', $png);

        $this->post(route('perfumes.store'), $payload)
            ->assertRedirect(route('perfumes.index'));

        $perfume = Perfume::firstOrFail();
        $this->assertStringStartsWith('images/products/', $perfume->image_url);
        $this->assertTrue(File::exists(public_path($perfume->image_url)));

        File::delete(public_path($perfume->image_url));
    }

    public function test_public_crud_pages_use_storefront_layout(): void
    {
        $perfume = Perfume::create($this->perfumeData());

        $homeResponse = $this->get(route('home'));
        $homeResponse
            ->assertOk()
            ->assertSee('aria-label="Điều hướng chính"', false)
            ->assertSee('id="sn-search"', false)
            ->assertDontSee('store-product-actions', false)
            ->assertDontSee('Quản lý sản phẩm');
        $this->assertSame(1, substr_count($homeResponse->getContent(), 'class="ht-header soopi-header"'));

        $this->get(route('perfumes.index'))
            ->assertOk()
            ->assertSee('Quản lý sản phẩm', false)
            ->assertSee('Xem')
            ->assertSee('Sửa')
            ->assertSee('Xóa')
            ->assertDontSee('SCENTORY ADMIN');

        $this->get(route('perfumes.create'))
            ->assertOk()
            ->assertSee('Thêm sản phẩm')
            ->assertSee('Tải ảnh sản phẩm từ máy')
            ->assertDontSee('SCENTORY ADMIN');

        $this->get(route('perfumes.show', $perfume))
            ->assertOk()
            ->assertSee('Thêm vào giỏ')
            ->assertSee('Mua ngay')
            ->assertDontSee('SCENTORY ADMIN');

        $this->get(route('perfumes.edit', $perfume))
            ->assertOk()
            ->assertSee('Sửa sản phẩm')
            ->assertDontSee('SCENTORY ADMIN');
    }

    public function test_gender_and_category_filters_show_separate_products(): void
    {
        $men = Category::create(['name' => 'Nước hoa nam']);
        $women = Category::create(['name' => 'Nước hoa nữ']);

        Perfume::create($this->perfumeData([
            'category_id' => $men->id,
            'name' => 'Hương Nam Riêng',
            'slug' => 'huong-nam-rieng',
            'gender' => 'nam',
        ]));
        Perfume::create($this->perfumeData([
            'category_id' => $women->id,
            'name' => 'Hương Nữ Riêng',
            'slug' => 'huong-nu-rieng',
            'gender' => 'nu',
        ]));

        $this->get(route('home', ['gender' => 'nam']))
            ->assertOk()
            ->assertSee('Hương Nam Riêng')
            ->assertDontSee('Hương Nữ Riêng');

        $this->get(route('home', ['category' => $women->id]))
            ->assertOk()
            ->assertSee('Hương Nữ Riêng')
            ->assertDontSee('Hương Nam Riêng');
    }

    public function test_search_results_are_shown_without_the_homepage_hero(): void
    {
        Perfume::create($this->perfumeData([
            'name' => 'Cristiano Ronaldo CR7',
            'slug' => 'cristiano-ronaldo-cr7',
        ]));

        $this->get(route('home', ['search' => 'ronaldo']))
            ->assertOk()
            ->assertSee('Cristiano Ronaldo CR7')
            ->assertSee('Kết quả cho')
            ->assertSee('ht-products-section', false)
            ->assertDontSee('ht-hero-grid', false);
    }

    private function perfumeData(array $overrides = []): array
    {
        return array_merge([
            'category_id' => null,
            'name' => 'Bleu de Chanel',
            'slug' => 'bleu-de-chanel',
            'brand' => 'Chanel',
            'gender' => 'nam',
            'concentration' => 'EDP',
            'volume_ml' => 100,
            'weight' => 200,
            'price' => 3900000,
            'sale_price' => 3500000,
            'stock' => 12,
            'image_url' => 'https://example.com/bleu-de-chanel.jpg',
            'description' => 'Hương gỗ thơm nam tính và thanh lịch.',
            'is_active' => true,
        ], $overrides);
    }
}
