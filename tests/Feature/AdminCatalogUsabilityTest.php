<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCatalogUsabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_admin_created_account_is_verified_and_password_is_hashed(): void
    {
        $this->post(route('admin.users.store'), [
            'name' => 'Lan Anh', 'email' => 'new-account@example.test',
            'password' => 'account-password', 'role' => 'user',
        ])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();

        $user = User::where('email', 'new-account@example.test')->firstOrFail();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue(Hash::check('account-password', $user->password));
    }

    public function test_current_admin_cannot_remove_their_own_admin_access(): void
    {
        $admin = auth()->user();
        $this->from(route('admin.users.edit', $admin))->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'user',
        ])->assertRedirect(route('admin.users.edit', $admin))->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->fresh()->role);
        $this->get(route('admin.users.index'))->assertOk();
    }

    public function test_edit_preserves_legacy_customer_role_and_password_when_left_blank(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $password = $user->password;
        $this->get(route('admin.users.edit', $user))->assertOk()
            ->assertSee('value="customer" selected', false);

        $this->put(route('admin.users.update', $user), [
            'name' => 'Updated name', 'email' => $user->email, 'role' => 'customer', 'password' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame('customer', $user->fresh()->role);
        $this->assertSame($password, $user->fresh()->password);
    }

    public function test_product_visibility_stays_unchecked_after_validation_failure(): void
    {
        $product = $this->product();
        $this->from(route('admin.products.edit', $product))->put(route('admin.products.update', $product), [
            ...$product->only(['brand', 'gender', 'volume_ml', 'price', 'stock']),
            'name' => '', 'is_active' => '0',
        ])->assertSessionHasErrors('name');

        $html = $this->get(route('admin.products.edit', $product))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<input[^>]*type="hidden"[^>]*name="is_active"[^>]*value="0"/', $html);
        preg_match('/<input[^>]*type="checkbox"[^>]*id="is_active"[^>]*>/', $html, $checkbox);
        $this->assertNotEmpty($checkbox);
        $this->assertStringNotContainsString('checked', $checkbox[0]);
        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_product_detail_shows_discount_and_all_inventory_sizes(): void
    {
        $product = $this->product(['volume_ml' => 75, 'sale_price' => 0, 'stock_5ml' => 8]);
        $this->get(route('admin.products.show', $product))->assertOk()
            ->assertSee('Chai gốc 75ml')->assertSee('Mẫu thử 5ml')
            ->assertSee('0₫</strong>', false)->assertSee('<del>1.200.000₫</del>', false);
    }

    public function test_admin_category_search_paginates_and_preserves_global_counts(): void
    {
        foreach (range(1, 16) as $index) {
            Category::create(['name' => 'Niche '.$index]);
        }
        Category::create(['name' => 'Designer']);
        $this->get(route('admin.categories.index', ['search' => 'Niche']))->assertOk()
            ->assertViewHas('categories', fn ($categories) => $categories->total() === 16 && $categories->count() === 15)
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === 17)
            ->assertSee('search=Niche', false)->assertDontSee('Designer');
        $this->getJson(route('admin.categories.index', ['search' => ['bad']]))
            ->assertUnprocessable()->assertJsonValidationErrors('search');
    }

    public function test_category_details_use_original_volume_and_deleting_category_keeps_products(): void
    {
        $category = Category::create(['name' => 'Niche']);
        $product = $this->product(['category_id' => $category->id, 'volume_ml' => 75]);
        $this->get(route('admin.categories.show', $category))->assertOk()->assertSee('Chai gốc 75ml');
        $this->delete(route('admin.categories.destroy', $category))->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('perfumes', ['id' => $product->id, 'category_id' => null, 'deleted_at' => null]);
    }

    private function product(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Rose Demo', 'slug' => 'rose-demo', 'brand' => 'Soopi', 'gender' => 'unisex',
            'volume_ml' => 100, 'price' => 1200000, 'stock' => 10, 'stock_5ml' => 0,
            'stock_10ml' => 3, 'stock_50ml' => 2, 'is_active' => true,
        ], $attributes));
    }
}
