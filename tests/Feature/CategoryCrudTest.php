<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_category_crud_flow_works(): void
    {
        $createResponse = $this->post(route('categories.store'), [
            'name' => 'Nước hoa niche',
        ]);

        $category = Category::firstOrFail();
        $createResponse->assertRedirect(route('categories.index'));
        $this->get(route('categories.show', $category))
            ->assertOk()
            ->assertSee('Nước hoa niche');

        $this->put(route('categories.update', $category), [
            'name' => 'Nước hoa cao cấp',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Nước hoa cao cấp',
        ]);

        $this->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Quản lý danh mục')
            ->assertSee('Thêm danh mục')
            ->assertSee('Xem')
            ->assertSee('Sửa')
            ->assertSee('Xóa')
            ->assertDontSee('SCENTORY ADMIN');

        $this->get(route('categories.create'))
            ->assertOk()
            ->assertSee('Thêm danh mục')
            ->assertDontSee('SCENTORY ADMIN');

        $this->get(route('categories.edit', $category))
            ->assertOk()
            ->assertSee('Sửa danh mục')
            ->assertDontSee('SCENTORY ADMIN');

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Thêm & quản lý danh mục', false);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
