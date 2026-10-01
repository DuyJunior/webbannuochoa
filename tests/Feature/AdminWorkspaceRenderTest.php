<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Livestream;
use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Render every admin screen against isolated fixtures. Set SOOPI_EXPORT_ADMIN_AUDIT=1
 * to export HTML and a route-path manifest for a read-only browser screenshot audit.
 */
class AdminWorkspaceRenderTest extends TestCase
{
    use RefreshDatabase;

    private array $manifest = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Http::preventStrayRequests();
        config(['demo.enabled' => false, 'services.livekit.url' => '', 'services.livekit.key' => '', 'services.livekit.secret' => '']);
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(10, 30));
        if (! $this->exporting()) {
            $this->withoutVite();
        }
    }

    private function exporting(): bool
    {
        return getenv('SOOPI_EXPORT_ADMIN_AUDIT') === '1';
    }

    private function capture(string $name, array $parameters = [], string $variant = ''): TestResponse
    {
        $path = route($name, $parameters, false);
        $response = $this->get($path)->assertOk()->assertSee('<html', false);
        if ($this->exporting()) {
            $directory = storage_path('app/admin-audit/pages');
            File::ensureDirectoryExists($directory);
            $filename = str_replace('.', '-', $name).($variant ? '-'.$variant : '').'.html';
            File::put($directory.'/'.$filename, $response->getContent());
            $this->manifest[$path] = $filename;
            File::put($directory.'/manifest.json', json_encode($this->manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        return $response;
    }

    private function fixtures(): array
    {
        $admin = User::factory()->create(['name' => 'Minh Anh · Quản trị', 'email' => 'admin.audit@example.test', 'role' => 'admin']);
        $staff = User::factory()->create(['name' => 'Thu Hà · Livestream', 'email' => 'studio.audit@example.test', 'role' => 'livestream_staff']);
        $customer = User::factory()->create(['name' => 'Nguyễn Ngọc Linh', 'email' => 'linh.audit@example.test', 'role' => 'user']);
        User::factory()->create(['name' => 'Trần Hoàng Phúc', 'email' => 'phuc.audit@example.test', 'role' => 'customer', 'email_verified_at' => null]);
        $category = Category::create(['name' => 'Hương hoa & thanh lịch', 'slug' => 'audit-floral']);
        $woody = Category::create(['name' => 'Hương gỗ & ấm áp', 'slug' => 'audit-woody']);
        $products = collect();
        foreach ([
            ['Chanel Chance Eau Tendre', 'Chanel', 'chanel-chance-tendre.jpg', 2850000, 2490000, 18],
            ['Miss Dior Blooming Bouquet', 'Dior', 'miss-dior-blooming.jpg', 3200000, null, 3],
            ['Delina Exclusif', 'Parfums de Marly', 'delina-exclusif.jpg', 4950000, 4650000, 8],
            ['Narciso Musc Noir Rose', 'Narciso Rodriguez', 'narciso-musc-noir-rose.jpg', 2750000, null, 0],
            ['Tom Ford Rose Prick', 'Tom Ford', 'tom-ford-rose-prick.jpg', 6800000, null, 12],
        ] as $index => [$name, $brand, $image, $price, $sale, $stock]) {
            $this->assertFileExists(public_path('images/products/'.$image));
            $products->push(Perfume::create([
                'category_id' => $index === 4 ? $woody->id : $category->id, 'name' => $name,
                'slug' => 'audit-perfume-'.$index, 'brand' => $brand, 'gender' => $index === 4 ? 'unisex' : 'nu',
                'concentration' => 'Eau de Parfum', 'volume_ml' => 100, 'weight' => 250,
                'price' => $price, 'sale_price' => $sale, 'stock' => $stock, 'stock_5ml' => 20,
                'stock_10ml' => 12, 'stock_50ml' => 5, 'image_url' => 'images/products/'.$image,
                'description' => 'Hương thơm thanh lịch với tầng hương hoa mềm mại. Phù hợp cho những khoảnh khắc thường ngày và các dịp đặc biệt.',
                'is_active' => $index !== 3,
            ]));
        }
        $product = $products->first();
        $stream = Livestream::create(['title' => 'Khám phá hương hoa mùa thu', 'description' => 'Cùng Thu Hà chọn hương thơm và giải đáp cách sử dụng.',
            'source' => 'browser', 'status' => 'scheduled', 'starts_at' => now()->addDay()->setTime(20, 0),
            'created_by' => $admin->id, 'perfume_id' => $product->id, 'pinned_perfume_id' => $product->id]);
        $stream->products()->attach($products->take(3)->pluck('id')->all());
        Livestream::create(['title' => 'Bí quyết chọn nước hoa làm quà', 'source' => 'youtube', 'youtube_video_id' => 'auditvideo1',
            'status' => 'ended', 'starts_at' => now()->subDays(2), 'created_by' => $staff->id]);

        $orders = collect();
        foreach ([
            ['cod_ordered', 'pending', 'cod', 'pending'],
            ['cod_paid', 'delivering', 'cod', 'paid'],
            ['paid_momo', 'ready_to_pick', 'momo', 'paid'],
            ['completed', 'delivered', 'cod', 'paid'],
            ['cancelled', 'cancelled', 'cod', 'refund_pending'],
            ['pending', 'pending', 'momo', 'initiated'],
        ] as $index => [$status, $shipping, $gateway, $payment]) {
            $itemProduct = $products[$index % $products->count()];
            $order = Order::create(['user_id' => $customer->id, 'customer_name' => $customer->name,
                'phone' => '0901234567', 'address' => '25 Nguyễn Đình Chiểu, Phường Đa Kao, TP. Hồ Chí Minh',
                'total_price' => ($itemProduct->sale_price ?: $itemProduct->price) + 30000, 'ghn_total_fee' => 30000,
                'status' => $status, 'shipping_status' => $shipping, 'inventory_status' => 'unreserved', 'is_demo' => false,
                'ghn_order_code' => $index > 0 ? 'GHNAUDIT'.str_pad($index, 5, '0', STR_PAD_LEFT) : null,
                'gift_message' => $index === 0 ? 'Chúc bạn một ngày thật dịu dàng và nhiều niềm vui.' : null,
            ]);
            $order->forceFill(['created_at' => now()->subDays($index * 3)])->save();
            $order->items()->create(['perfume_id' => $itemProduct->id, 'price' => $itemProduct->sale_price ?: $itemProduct->price,
                'quantity' => 1, 'volume_ml' => 100, 'livestream_id' => $index === 1 ? $stream->id : null]);
            $order->paymentTransactions()->create(['gateway' => $gateway, 'status' => $payment, 'amount' => $order->total_price,
                'paid_at' => in_array($payment, ['paid', 'refund_pending']) ? $order->created_at->copy()->addHour() : null]);
            $orders->push($order);
        }
        $order = $orders->first();
        $demo = Order::create(['user_id' => $customer->id, 'customer_name' => 'Khách hàng mô phỏng', 'phone' => '0900000000',
            'address' => 'Địa chỉ mô phỏng', 'total_price' => 500000, 'status' => 'completed', 'shipping_status' => 'delivered', 'is_demo' => true]);
        $demo->paymentTransactions()->create(['gateway' => 'demo', 'status' => 'paid', 'amount' => 500000, 'paid_at' => now()]);

        // These tables have migration fixtures; replace them only inside this in-memory database.
        Article::query()->delete();
        Video::query()->delete();
        $article = Article::create(['title' => 'Chọn hương thơm cho những ngày thu', 'slug' => 'audit-autumn-guide',
            'excerpt' => 'Một chút hương hoa, một chút ấm áp: gợi ý để chọn mùi hương phù hợp với bạn.',
            'body' => "## Tìm một mùi hương riêng\n\nBắt đầu với những nốt hương bạn yêu thích và thử trên da trước khi lựa chọn.\n\n## Dành thời gian cảm nhận\n\nHãy để các tầng hương mở ra tự nhiên trong ngày.",
            'image_url' => '/images/journal/ritual.webp', 'is_published' => true]);
        Article::create(['title' => 'Cách giữ hương thơm bền lâu', 'slug' => 'audit-scent-care', 'excerpt' => 'Những thói quen nhỏ giúp lưu giữ mùi hương.',
            'body' => str_repeat('Bảo quản nước hoa ở nơi thoáng mát, tránh nắng trực tiếp. ', 3), 'image_url' => '/images/journal/detail.webp', 'is_published' => false]);
        $video = Video::create(['perfume_id' => $product->id, 'title' => 'Một phút cùng Chanel Chance', 'video_url' => '/videos/audit-preview.mp4',
            'thumbnail_url' => 'images/products/chanel-chance-tendre.jpg', 'description' => 'Khám phá vẻ dịu dàng của hương hoa.',
            'duration' => '00:45', 'views_count' => 1840, 'placement' => 'home', 'sort_order' => 1, 'is_active' => true]);
        Video::create(['perfume_id' => $products[1]->id, 'title' => 'Gợi ý quà tặng tinh tế', 'video_url' => '/videos/audit-gift.mp4',
            'thumbnail_url' => 'images/products/miss-dior-blooming.jpg', 'placement' => 'product', 'is_active' => false]);
        Coupon::create(['code' => 'AUTUMN10', 'type' => 'percent', 'value' => 10, 'minimum_order' => 1000000, 'usage_limit' => 100,
            'starts_at' => now()->subWeek(), 'expires_at' => now()->addMonth(), 'is_active' => true]);
        Coupon::create(['code' => 'WELCOME100', 'type' => 'fixed', 'value' => 100000, 'minimum_order' => 1500000,
            'starts_at' => now()->subMonth(), 'expires_at' => now()->subDay(), 'is_active' => false]);

        return compact('admin', 'staff', 'customer', 'category', 'product', 'order', 'article', 'video', 'stream');
    }

    public function test_all_admin_workspaces_render_with_populated_empty_and_role_variants(): void
    {
        extract($this->fixtures());
        $screens = [
            ['admin.dashboard', []], ['admin.products.index', []], ['admin.products.create', []],
            ['admin.products.show', [$product->id]], ['admin.products.edit', [$product->id]],
            ['admin.categories.index', []], ['admin.categories.create', []], ['admin.categories.show', [$category->id]], ['admin.categories.edit', [$category->id]],
            ['admin.users.index', []], ['admin.users.create', []], ['admin.users.show', [$customer->id]], ['admin.users.edit', [$customer->id]],
            ['admin.orders.index', []], ['admin.orders.show', [$order->id]],
            ['admin.finance.index', []], ['admin.finance.transactions', []], ['admin.reports.index', []], ['admin.reports.charts', []],
            ['admin.articles.index', []], ['admin.articles.create', []], ['admin.articles.edit', [$article->id]],
            ['admin.coupons.index', []], ['admin.videos.index', []], ['admin.videos.create', []], ['admin.videos.edit', [$video->id]],
            ['admin.livestreams.index', []], ['admin.livestreams.create', []], ['admin.livestreams.edit', [$stream->id]],
            ['admin.livestreams.report', [$stream->id]], ['admin.livestreams.studio', [$stream->id]],
        ];

        $this->actingAs($admin);
        foreach ($screens as [$name, $parameters]) {
            $this->capture($name, $parameters)->assertSee('id="admin-main"', false);
        }
        foreach (['products', 'categories', 'users', 'orders', 'articles', 'coupons', 'videos', 'livestreams'] as $resource) {
            $this->capture('admin.'.$resource.'.index', ['search' => 'AUDIT-NO-RESULT-8729'], 'empty');
        }
        foreach (['products' => 'Chanel', 'categories' => 'Hương hoa', 'users' => 'Ngọc Linh', 'orders' => '#DH00001',
            'articles' => 'ngày thu', 'coupons' => 'AUTUMN', 'videos' => 'Chanel', 'livestreams' => 'mùa thu'] as $resource => $search) {
            $this->capture('admin.'.$resource.'.index', ['search' => $search], 'search');
        }
        foreach (['admin.finance.index', 'admin.finance.transactions'] as $name) {
            $this->capture($name, ['search' => 'AUDIT-NO-RESULT-8729'], 'empty');
            $this->capture($name, ['mode' => 'demo'], 'demo');
        }
        foreach (['admin.reports.index', 'admin.reports.charts'] as $name) {
            $this->capture($name, ['date_from' => '2020-01-01', 'date_to' => '2020-01-31'], 'empty');
            $this->capture($name, ['mode' => 'demo'], 'demo');
        }

        $this->actingAs($staff);
        foreach ($screens as [$name, $parameters]) {
            if (str_starts_with($name, 'admin.livestreams.')) {
                $this->capture($name, array_merge($parameters, ['audit_role' => 'staff']), 'staff')
                    ->assertDontSee('href="'.route('admin.products.index').'"', false)
                    ->assertDontSee('href="'.route('admin.finance.index').'"', false);
            } else {
                $this->get(route($name, $parameters))->assertRedirect(route('home'));
            }
        }
        $this->actingAs($customer);
        foreach ($screens as [$name, $parameters]) {
            $response = $this->get(route($name, $parameters));
            if (str_starts_with($name, 'admin.livestreams.')) {
                $response->assertForbidden();
            } else {
                $response->assertRedirect(route('home'));
            }
        }
        Auth::logout();
        $this->capture('admin.login');
        Http::assertNothingSent();
        $this->assertSame('scheduled', $stream->fresh()->status);
        $this->assertNull($stream->fresh()->last_heartbeat_at);
    }
}
