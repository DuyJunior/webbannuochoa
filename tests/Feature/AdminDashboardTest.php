<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_real_totals_and_daily_values_without_cancelled_orders(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 29)->startOfDay()->addHours(12));
        $admin = User::factory()->create(['role' => 'admin']);
        foreach ([['pending', 120000, now()], ['cancelled', 900000, now()], ['paid', 50000, now()->subDays(12)]] as [$status, $amount, $date]) {
            $order = Order::create(['name' => 'Khách thử nghiệm', 'phone' => '0900000000', 'address' => 'Hà Nội', 'status' => $status, 'shipping_status' => 'pending', 'total_price' => $amount]);
            $order->timestamps = false;
            $order->created_at = $date;
            $order->saveQuietly();
        }
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk()->assertSee('Cửa hàng trong tầm tay.')->assertSee('Xem báo cáo');
        $response->assertViewHas('stats', fn ($stats) => $stats['orders'] === 3 && $stats['value'] === 170000.0 && $stats['pending'] === 2);
        $response->assertViewHas('chart', fn ($chart) => $chart->count() === 7 && $chart->sum('amount') === 120000.0 && $chart->sum('count') === 1);
        $this->get(route('admin.dashboard', ['days' => 30]))->assertOk()
            ->assertViewHas('chart', fn ($chart) => $chart->count() === 30 && $chart->sum('amount') === 170000.0);
    }

    public function test_empty_dashboard_renders_without_chart_errors(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Chưa có đơn hàng trong khoảng thời gian này.');
    }

    public function test_unsupported_period_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->getJson(route('admin.dashboard', ['days' => 999]))
            ->assertUnprocessable()->assertJsonValidationErrors('days');
    }

    public function test_customer_cannot_view_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))->get(route('admin.dashboard'))->assertRedirect(route('home'));
    }
}
