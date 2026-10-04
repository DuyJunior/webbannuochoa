<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_article_content_metadata_and_cards_use_english_then_restore_vietnamese(): void
    {
        $article = Article::create(['title' => 'Câu chuyện hương thơm', 'slug' => 'story', 'excerpt' => 'Tóm tắt tiếng Việt',
            'body' => str_repeat('Nội dung tiếng Việt. ', 4), 'title_en' => 'A fragrance story', 'excerpt_en' => 'An English introduction',
            'body_en' => "The first English paragraph.\n\nThe second English paragraph contains <script>alert(1)</script>.", 'is_published' => true]);
        $this->withSession(['locale' => 'en'])->get('/cam-nang/story')->assertOk()
            ->assertSee('<title>A fragrance story · Soopi Journal</title>', false)
            ->assertSee('content="An English introduction"', false)
            ->assertSee('The first English paragraph.')->assertDontSee('Nội dung tiếng Việt')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/cam-nang')->assertSee('A fragrance story')->assertSee('An English introduction');
        $this->withSession(['locale' => 'vi'])->get('/cam-nang/story')->assertSee('Câu chuyện hương thơm')->assertSee('Nội dung tiếng Việt')->assertDontSee('The first English paragraph.');
        $this->assertSame('story', $article->fresh()->slug);
    }

    public function test_existing_editorial_translation_handles_line_endings_and_does_not_reuse_stale_copy(): void
    {
        app()->setLocale('en');
        $dictionary = json_decode(file_get_contents(lang_path('en.json')), true);
        $body = collect(array_keys($dictionary))->first(fn ($key) => str_starts_with($key, 'Cổ tay và hai bên cổ'));
        $article = new Article(['title' => 'Xịt nước hoa ở đâu để cảm nhận dễ chịu?', 'body' => str_replace("\n", "\r\n", $body)]);
        $this->assertSame('Where to Apply Perfume for a Subtle Scent', $article->localized_title);
        $this->assertStringContainsString('Your wrists and the sides of your neck', $article->localized_body);
        $article->body = 'Nội dung mới chưa dịch';
        $this->assertSame('Nội dung mới chưa dịch', $article->localized_body);
        $article->body_en = 'An explicit updated translation.';
        $this->assertSame('An explicit updated translation.', $article->localized_body);
    }

    public function test_admin_can_edit_english_article_fields_without_changing_original_or_slug(): void
    {
        $article = Article::create(['title' => 'Bài viết gốc', 'slug' => 'bai-viet-goc', 'excerpt' => 'Tóm tắt', 'body' => str_repeat('Nội dung. ', 10), 'is_published' => true]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = $article->only('title', 'excerpt', 'body', 'is_published') + ['title_en' => 'English story', 'excerpt_en' => 'English excerpt', 'body_en' => str_repeat('English text. ', 8)];
        $this->put('/admin/articles/'.$article->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'slug' => 'bai-viet-goc', 'title' => 'Bài viết gốc', 'title_en' => 'English story']);
        $this->get('/admin/articles?search=English')->assertViewHas('articles', fn ($articles) => $articles->contains('id', $article->id));
        $this->postJson('/admin/articles', array_merge($data, ['title_en' => str_repeat('x', 201), 'body_en' => ['invalid']]))
            ->assertUnprocessable()->assertJsonValidationErrors(['title_en', 'body_en']);
    }

    public function test_product_and_category_translations_leave_catalog_values_and_admin_edit_inputs_unchanged(): void
    {
        $category = Category::create(['name' => 'Bộ hương riêng', 'name_en' => 'Private collection']);
        $perfume = Perfume::create(['category_id' => $category->id, 'name' => 'Nước hoa thử', 'name_en' => 'Test fragrance',
            'description' => 'Mô tả gốc', 'description_en' => 'English fragrance description', 'brand' => 'Soopi',
            'slug' => 'test-fragrance', 'gender' => 'nu', 'price' => 1500000, 'volume_ml' => 100, 'stock' => 3, 'is_active' => true]);
        $this->withSession(['locale' => 'en'])->get('/')->assertSee('Test fragrance');
        $this->get('/perfumes/'.$perfume->id)->assertSee('Private collection')->assertSee('Test fragrance');
        $this->getJson('/perfumes/'.$perfume->id.'/quick-view')->assertJsonPath('name', 'Test fragrance');
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/products/'.$perfume->id.'/edit')
            ->assertOk()->assertSee('value="Nước hoa thử"', false)->assertSee('name="description_en"', false);
        $this->get('/admin/products/create')->assertOk()->assertSee('English name');
        $this->get('/admin/categories/create')->assertOk()->assertSee('English name');
        $this->assertSame('Nước hoa thử', $perfume->fresh()->name);
        $this->assertSame(3, $perfume->fresh()->stock);
    }

    public function test_admin_saves_and_validates_product_and_category_english_copy(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/categories', ['name' => 'Hương riêng', 'name_en' => 'Signature scents'])->assertSessionHasNoErrors();
        $category = Category::where('name', 'Hương riêng')->sole();
        $this->assertSame('Signature scents', $category->name_en);
        $product = Perfume::create(['name' => 'Nước hoa thử', 'brand' => 'Soopi', 'slug' => 'sample-fragrance',
            'gender' => 'nam', 'price' => 1500000, 'volume_ml' => 100, 'weight' => 200, 'stock' => 3,
            'stock_5ml' => 2, 'stock_10ml' => 2, 'stock_50ml' => 2, 'is_active' => true]);
        $payload = $product->only('name', 'brand', 'gender', 'price', 'volume_ml', 'weight', 'stock', 'stock_5ml', 'stock_10ml', 'stock_50ml', 'is_active')
            + ['description' => 'Mô tả gốc', 'name_en' => 'Sample fragrance', 'description_en' => 'A delicate English description.'];
        $this->put('/admin/products/'.$product->id, $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('perfumes', ['id' => $product->id, 'name' => 'Nước hoa thử', 'name_en' => 'Sample fragrance',
            'description' => 'Mô tả gốc', 'description_en' => 'A delicate English description.', 'stock' => 3]);
        $this->withSession(['locale' => 'en'])->get('/hop-thu-mui')->assertOk()->assertSee('Sample fragrance');
        $this->putJson('/admin/products/'.$product->id, array_merge($payload, ['name_en' => str_repeat('x', 256)]))
            ->assertUnprocessable()->assertJsonValidationErrors('name_en');
        $this->putJson('/admin/categories/'.$category->id, ['name' => 'Hương riêng', 'name_en' => ['invalid']])
            ->assertUnprocessable()->assertJsonValidationErrors('name_en');
    }
}
