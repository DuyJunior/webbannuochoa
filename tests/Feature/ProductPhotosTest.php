<?php

namespace Tests\Feature;

use App\Models\Perfume;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class ProductPhotosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Storage::fake('public');
    }

    public function test_admin_can_create_separate_actual_photos_without_replacing_catalog_artwork(): void
    {
        $this->asAdmin();
        $this->post(route('admin.products.store'), [
            ...$this->productData(),
            'shop_photos' => [$this->photo('bottle.png'), $this->photo('box.png')],
        ])->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $product = Product::firstOrFail();
        $this->assertSame('images/products/catalog.jpg', $product->image_url);
        $this->assertCount(2, $product->shopPhotos);
        foreach ($product->shopPhotos as $photo) {
            $this->assertStringStartsWith('products/photos/', $photo->path);
            Storage::disk('public')->assertExists($photo->path);
        }
        $this->assertCount(2, Perfume::findOrFail($product->id)->shopPhotos);
        $this->get(route('admin.products.edit', $product))->assertOk()
            ->assertSee('Ảnh thực tế sản phẩm')->assertSee('remove_shop_photos[]', false);
    }

    public function test_admin_can_remove_owned_photos_and_add_new_ones_in_one_save(): void
    {
        $this->asAdmin();
        $product = $this->product();
        $removed = $this->savedPhoto($product);
        $kept = $this->savedPhoto($product);

        $this->put(route('admin.products.update', $product), [
            ...$this->productData(),
            'remove_shop_photos' => [$removed->id],
            'shop_photos' => [$this->photo()],
        ])->assertRedirect(route('admin.products.show', $product))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('product_photos', ['id' => $removed->id]);
        Storage::disk('public')->assertMissing($removed->path);
        Storage::disk('public')->assertExists($kept->path);
        $this->assertCount(2, $product->fresh()->shopPhotos);
        $this->assertSame('images/products/catalog.jpg', $product->fresh()->image_url);
    }

    public function test_photos_from_another_product_cannot_be_removed(): void
    {
        $this->asAdmin();
        $product = $this->product();
        $otherPhoto = $this->savedPhoto($this->product());

        $this->put(route('admin.products.update', $product), [
            ...$this->productData(['name' => 'Should not be saved']),
            'remove_shop_photos' => [$otherPhoto->id],
            'shop_photos' => [$this->photo()],
        ])->assertSessionHasErrors('remove_shop_photos');

        $this->assertSame('Shop photo perfume', $product->fresh()->name);
        $this->assertDatabaseHas('product_photos', ['id' => $otherPhoto->id]);
        Storage::disk('public')->assertExists($otherPhoto->path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_total_photo_limit_includes_existing_photos_but_allows_replacement(): void
    {
        $this->asAdmin();
        $product = $this->product();
        foreach (range(1, 6) as $index) {
            $this->savedPhoto($product);
        }

        $this->put(route('admin.products.update', $product), [
            ...$this->productData(), 'shop_photos' => [$this->photo()],
        ])->assertSessionHasErrors('shop_photos');
        $this->assertCount(6, Storage::disk('public')->allFiles());

        $this->put(route('admin.products.update', $product), [
            ...$this->productData(), 'shop_photos' => [$this->photo()],
            'remove_shop_photos' => [$product->shopPhotos()->firstOrFail()->id],
        ])->assertSessionHasNoErrors();
        $this->assertCount(6, $product->fresh()->shopPhotos);
        $this->assertCount(6, Storage::disk('public')->allFiles());
    }

    public function test_rejected_gallery_change_does_not_leave_an_orphaned_catalog_upload(): void
    {
        $this->asAdmin();
        Storage::fake('catalog');
        $this->app->usePublicPath(Storage::disk('catalog')->path(''));
        $product = $this->product();
        $otherPhoto = $this->savedPhoto($this->product());

        $this->put(route('admin.products.update', $product), [
            ...$this->productData(), 'image_file' => $this->photo('catalog.png'),
            'remove_shop_photos' => [$otherPhoto->id],
        ])->assertSessionHasErrors('remove_shop_photos');

        $this->assertEmpty(Storage::disk('catalog')->allFiles());
        $this->assertSame('images/products/catalog.jpg', $product->fresh()->image_url);
        Storage::disk('public')->assertExists($otherPhoto->path);
    }

    public function test_upload_rejects_svg_executable_extension_invalid_content_and_oversize_files(): void
    {
        $this->asAdmin();
        $invalidFiles = [
            UploadedFile::fake()->createWithContent('photo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            $this->photo('photo.php'),
            UploadedFile::fake()->createWithContent('photo.jpg', '<?php echo "not a photo";'),
            $this->photo()->size(5121),
        ];

        foreach ($invalidFiles as $file) {
            $this->post(route('admin.products.store'), [
                ...$this->productData(), 'shop_photos' => [$file],
            ])->assertSessionHasErrors('shop_photos.0');
        }
        $this->assertDatabaseCount('perfumes', 0);
        $this->assertDatabaseCount('product_photos', 0);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_invalid_array_and_duplicate_removals_are_rejected(): void
    {
        $this->asAdmin();
        $product = $this->product();
        $photo = $this->savedPhoto($product);

        $this->put(route('admin.products.update', $product), [
            ...$this->productData(), 'remove_shop_photos' => [$photo->id, $photo->id],
        ])->assertSessionHasErrors('remove_shop_photos.0');

        $this->post(route('admin.products.store'), [
            ...$this->productData(), 'shop_photos' => 'products/photos/not-an-upload.png',
        ])->assertSessionHasErrors('shop_photos');
        Storage::disk('public')->assertExists($photo->path);
    }

    public function test_combined_new_photo_uploads_cannot_exceed_eighteen_megabytes(): void
    {
        $this->asAdmin();
        $this->post(route('admin.products.store'), [
            ...$this->productData(),
            'shop_photos' => array_map(fn ($index) => $this->photo('photo-'.$index.'.png')->size(5120), range(1, 4)),
        ])->assertSessionHasErrors('shop_photos');

        $this->assertDatabaseCount('perfumes', 0);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_guest_and_customer_cannot_upload_or_remove_photos(): void
    {
        $product = $this->product();
        $photo = $this->savedPhoto($product);
        $payload = [...$this->productData(), 'remove_shop_photos' => [$photo->id], 'shop_photos' => [$this->photo()]];

        $this->put(route('admin.products.update', $product), $payload)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->put(route('admin.products.update', $product), $payload)->assertRedirect(route('home'));
        $this->post(route('admin.products.store'), $payload)->assertRedirect(route('home'));
        Storage::disk('public')->assertExists($photo->path);
        $this->assertDatabaseCount('product_photos', 1);
    }

    public function test_public_gallery_shows_only_uploaded_photos_with_accessible_thumbnails(): void
    {
        $product = $this->product();
        $first = $this->savedPhoto($product);
        $second = $this->savedPhoto($product);

        $this->get(route('perfumes.show', $product))->assertOk()
            ->assertSee('Ảnh thực tế sản phẩm')->assertSee($first->url)->assertSee($second->url)
            ->assertSee('aria-label="Chọn ảnh thực tế sản phẩm"', false)
            ->assertSee('aria-current="true"', false)->assertSee('data-shop-photo-main', false);
    }

    public function test_product_without_uploads_does_not_claim_to_have_actual_photos(): void
    {
        $product = $this->product();
        config(['scent-gallery.artwork.'.$product->slug => 'images/generated-artwork.webp']);

        $this->get(route('perfumes.show', $product))->assertOk()
            ->assertSee('images/generated-artwork.webp')
            ->assertDontSee('Ảnh thực tế sản phẩm')->assertDontSee('data-shop-photo-gallery', false);
    }

    public function test_archiving_product_preserves_photos_and_public_access_stays_hidden(): void
    {
        $this->asAdmin();
        $product = $this->product();
        $photo = $this->savedPhoto($product);

        $this->delete(route('admin.products.destroy', $product))->assertRedirect(route('admin.products.index'));
        $this->assertSoftDeleted('perfumes', ['id' => $product->id]);
        $this->assertDatabaseHas('product_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertExists($photo->path);
        $this->assertTrue($photo->perfume->trashed());
        $this->get(route('perfumes.show', $product))->assertNotFound();
    }

    public function test_failed_database_write_keeps_existing_photos_and_cleans_new_uploads(): void
    {
        $this->asAdmin();
        $product = $this->product();
        $photo = $this->savedPhoto($product);
        ProductPhoto::creating(function () {
            throw new RuntimeException('Simulated database failure');
        });

        try {
            $this->put(route('admin.products.update', $product), [
                ...$this->productData(['name' => 'Must roll back']),
                'shop_photos' => [$this->photo()], 'remove_shop_photos' => [$photo->id],
            ])->assertStatus(500);
        } finally {
            ProductPhoto::flushEventListeners();
        }

        $this->assertSame('Shop photo perfume', $product->fresh()->name);
        $this->assertDatabaseHas('product_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertExists($photo->path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    private function asAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function productData(array $attributes = []): array
    {
        return array_merge([
            'name' => 'Shop photo perfume', 'brand' => 'Soopi', 'gender' => 'unisex',
            'volume_ml' => 100, 'price' => 1200000, 'stock' => 10,
            'image_url' => 'images/products/catalog.jpg', 'is_active' => true,
        ], $attributes);
    }

    private function product(): Product
    {
        return Product::create([...$this->productData(), 'slug' => (string) Str::uuid()]);
    }

    private function photo(string $name = 'actual-photo.png'): File
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
    }

    private function savedPhoto(Product $product): ProductPhoto
    {
        $path = $this->photo()->store('products/photos', 'public');

        return $product->shopPhotos()->create(['path' => $path]);
    }
}
