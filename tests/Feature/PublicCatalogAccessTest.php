<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_catalog_and_categories_use_storefront_routes_only(): void
    {
        $this->assertPublicCollections();
    }

    public function test_customer_catalog_and_categories_do_not_expose_management_or_hidden_items(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $this->assertPublicCollections();
    }

    public function test_public_catalog_rejects_malformed_filters(): void
    {
        $this->getJson(route('perfumes.index', ['search' => ['bad'], 'gender' => 'invalid']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['search', 'gender']);
    }

    public function test_admin_stock_labels_follow_the_original_bottle_volume_without_changing_stock(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = $this->perfume(['volume_ml' => 75, 'stock' => 7]);

        $this->get(route('admin.products.index'))->assertOk()->assertSee('75 ml · Chai gốc: 7 chai');
        $this->get(route('admin.products.show', $product))->assertOk()->assertSee('Chai gốc 75ml');
        $this->get(route('admin.products.create'))->assertOk()->assertSee('Kho chai gốc 100 ml');
        $this->get(route('admin.products.edit', $product))->assertOk()->assertSee('Kho chai gốc 75 ml');

        $this->assertDatabaseHas('perfumes', ['id' => $product->id, 'volume_ml' => 75, 'stock' => 7]);
    }

    private function assertPublicCollections(): void
    {
        $category = Category::create(['name' => 'Bộ sưu tập đang bán']);
        $hiddenCategory = Category::create(['name' => 'Bộ sưu tập chưa công bố']);
        $visible = $this->perfume(['category_id' => $category->id]);
        $hidden = $this->perfume([
            'category_id' => $category->id, 'name' => 'Sản phẩm đang ẩn',
            'slug' => 'hidden-perfume', 'is_active' => false,
        ]);
        $this->perfume([
            'category_id' => $hiddenCategory->id, 'name' => 'Chai thử nội bộ',
            'slug' => 'internal-perfume', 'is_active' => false,
        ]);

        foreach (['categories.index', 'user.categories.index'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertViewIs('store.categories')
                ->assertViewHas('categories', fn ($categories) => $categories->count() === 1
                    && $categories->first()->id === $category->id
                    && $categories->first()->perfumes_count === 1)
                ->assertSee($category->name)
                ->assertDontSee($hiddenCategory->name)
                ->assertDontSee('Quản lý danh mục')
                ->assertDontSee(route('categories.create'), false)
                ->assertDontSee(route('categories.edit', $category), false);
        }

        $filters = ['search' => 'Visible', 'gender' => 'nu', 'category' => $category->id, 'sort' => 'price_asc'];
        $this->get(route('perfumes.index', [...$filters, 'status' => 'inactive']))
            ->assertRedirect(route('home', $filters).'#san-pham');
        $this->get(route('categories.show', $category))
            ->assertRedirect(route('home', ['category' => $category->id]).'#san-pham');
        $this->get(route('home', $filters))->assertOk()
            ->assertSee($visible->name)
            ->assertDontSee($hidden->name)
            ->assertDontSee(route('perfumes.edit', $visible), false);
    }

    private function perfume(array $overrides = []): Perfume
    {
        return Perfume::create(array_merge([
            'name' => 'Visible Rose', 'slug' => 'visible-rose', 'brand' => 'Soopi',
            'gender' => 'nu', 'concentration' => 'EDP', 'volume_ml' => 100,
            'weight' => 200, 'price' => 1500000, 'stock' => 5,
            'description' => 'Hương hoa dịu nhẹ.', 'is_active' => true,
        ], $overrides));
    }
}
