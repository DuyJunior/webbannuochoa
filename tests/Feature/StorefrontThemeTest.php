<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StorefrontThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_pages_share_the_theme_without_losing_purchase_forms(): void
    {
        $export = getenv('SOOPI_EXPORT_REVIEW') === '1';
        if (! $export) {
            $this->withoutVite();
        }
        $category = Category::create(['name' => 'Hương hoa']);
        $product = Perfume::create([
            'name' => 'Miss Dior Blooming Bouquet', 'slug' => 'bloom-review',
            'brand' => 'Dior', 'gender' => 'nu', 'concentration' => 'EDT',
            'volume_ml' => 100, 'price' => 3390000, 'stock' => 20,
            'category_id' => $category->id, 'is_active' => true,
            'description' => 'Hương hoa hồng dịu dàng, thanh lịch cho mỗi ngày.',
            'image_url' => 'images/products/miss-dior-blooming.jpg',
        ]);
        $article = Article::create(['title' => 'Nghệ thuật chọn một mùi hương', 'slug' => 'atelier-review',
            'excerpt' => 'Tìm một dấu hương mang câu chuyện của riêng bạn.',
            'body' => "Hãy thử nước hoa trên làn da và dành thời gian cảm nhận.\nMỗi tầng hương hé mở một nét riêng.",
            'image_url' => 'images/bloom/silk-atelier.webp', 'is_published' => true]);
        $user = User::factory()->create(['name' => 'Khách trải nghiệm', 'email' => 'design-review@example.test', 'role' => 'customer']);
        $order = Order::create(['user_id' => $user->id, 'customer_name' => $user->name,
            'phone' => '0900000000', 'address' => 'Địa chỉ minh họa', 'total_price' => 3390000, 'status' => 'pending']);
        $order->items()->create(['perfume_id' => $product->id, 'quantity' => 1, 'price' => 3390000]);

        $pages = [
            'home' => '/', 'catalog' => '/?gender=nu', 'product' => '/perfumes/'.$product->id,
            'finder' => '/chon-huong', 'finder-results' => '/chon-huong?style=hoa',
            'compare' => '/so-sanh?ids='.$product->id, 'journal' => '/cam-nang',
            'article' => '/cam-nang/'.$article->slug, 'quiz' => '/quiz',
            'discovery' => '/hop-thu-mui', 'scent-today' => '/mui-huong-hom-nay',
            'faq' => '/cau-hoi-thuong-gap', 'live' => '/livestream',
            'login' => '/login', 'register' => '/register',
        ];
        $directory = storage_path('app/backups/atelier-review');
        if ($export) File::ensureDirectoryExists($directory);
        $save = function ($name, $url) use ($export, $directory) {
            $response = $this->get($url)->assertOk()->assertSee('soopi-store', false)
                ->assertSee('atelier-footer-invitation', false);
            if ($export) File::put($directory.'/'.$name.'.html', $response->getContent());
            return $response;
        };
        foreach ($pages as $name => $url) $save($name, $url);
        $this->actingAs($user)->withSession(['cart' => [$product->id => 1]]);
        foreach (['cart' => '/gio-hang', 'checkout' => '/payment', 'account' => '/tai-khoan',
            'orders' => '/orders', 'order-detail' => '/orders/'.$order->id,
            'tracking' => '/kiem-tra-don-hang', 'wishlist' => '/yeu-thich',
            'wardrobe' => '/tu-nuoc-hoa', 'member' => '/thanh-vien', 'verify' => '/email/verify'] as $name => $url) {
            $response = $save($name, $url);
            if ($name === 'checkout') $response->assertSee('id="checkoutPaymentForm"', false)->assertSee('name="checkout_key"', false);
            if ($name === 'account') $response->assertSee('name="current_password"', false);
        }
    }
}
