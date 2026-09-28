<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Perfume;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\ProductionSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Pdo\Mysql;
use RuntimeException;
use Tests\TestCase;

class DeploymentReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        config(['seeding.admin' => ['name' => 'Deployment Admin', 'email' => 'deploy@example.test', 'password' => 'UniqueTestPassword123!']]);
    }

    public function test_production_seeding_is_repeatable_and_preserves_accounts_prices_stock_and_archives(): void
    {
        $this->seed(ProductionSeeder::class);
        $admin = User::where('email', 'deploy@example.test')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('UniqueTestPassword123!', $admin->password));
        $this->assertGreaterThanOrEqual(10, Perfume::count());
        $this->assertGreaterThanOrEqual(3, Category::count());
        $product = Perfume::firstOrFail();
        $product->update(['price' => 987654, 'stock' => 3, 'name' => 'Admin edited name']);
        $product->delete();
        $initialProducts = Perfume::withTrashed()->count();
        $initialCategories = Category::count();
        config(['seeding.admin.password' => 'AnotherConfiguredPassword999!']);
        $this->seed(ProductionSeeder::class);
        $this->assertSame(1, User::count());
        $this->assertSame($initialProducts, Perfume::withTrashed()->count());
        $this->assertSame($initialCategories, Category::count());
        $this->assertSame($admin->password, $admin->fresh()->password);
        $preserved = Perfume::withTrashed()->findOrFail($product->id);
        $this->assertTrue($preserved->trashed());
        $this->assertSame('Admin edited name', $preserved->name);
        $this->assertSame(3, $preserved->stock);
        $this->assertEquals(987654, $preserved->price);
    }

    public function test_seeder_never_promotes_an_existing_customer_with_matching_email(): void
    {
        $user = User::factory()->create(['email' => 'deploy@example.test', 'role' => 'user']);
        try {
            $this->seed(ProductionSeeder::class);
            $this->fail('Expected account collision rejection.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('non-admin', $exception->getMessage());
        }
        $this->assertSame('user', $user->fresh()->role);
        $this->assertDatabaseCount('perfumes', 0);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_seeder_requires_operator_credentials_instead_of_a_default_password(): void
    {
        foreach ([['email' => '', 'password' => 'ValidPassword123'], ['email' => 'deploy@example.test', 'password' => 'short']] as $credentials) {
            config(['seeding.admin' => $credentials]);
            try {
                $this->seed(ProductionSeeder::class);
                $this->fail('Expected invalid seed configuration rejection.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('SEED_ADMIN_', $exception->getMessage());
            }
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('perfumes', 0);
    }

    public function test_mysql_tls_verification_defaults_to_enabled(): void
    {
        if (! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('PDO MySQL is not installed on this runner.');
        }
        $key = PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_VERIFY_SERVER_CERT : \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT;
        $this->assertTrue(config('database.connections.mysql.options')[$key]);
    }

    public function test_existing_laravel_health_endpoint_responds(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_expiry_scheduler_has_a_bounded_recovery_lock(): void
    {
        $this->app->make(Kernel::class)->bootstrap();
        $event = collect($this->app->make(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command ?? '', 'orders:expire-unpaid'));
        $this->assertNotNull($event);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(5, $event->expiresAt);
        $this->assertSame('* * * * *', $event->expression);
    }

    public function test_https_forwarding_only_applies_to_configured_proxies(): void
    {
        config(['deployment.trusted_proxies' => ['10.20.30.40']]);
        (new AppServiceProvider($this->app))->boot();
        Route::get('/_test/proxy', function (Request $request) {
            return response()->json(['secure' => $request->isSecure(), 'url' => $request->url()]);
        });

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'shop.example.test', 'X-Forwarded-Port' => '443'])
            ->get('http://localhost/_test/proxy')
            ->assertOk()->assertJson(['secure' => true, 'url' => 'https://shop.example.test/_test/proxy']);
        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.41'])
            ->get('http://localhost/_test/proxy')
            ->assertOk()->assertJson(['secure' => false, 'url' => 'http://localhost/_test/proxy']);
    }
}
