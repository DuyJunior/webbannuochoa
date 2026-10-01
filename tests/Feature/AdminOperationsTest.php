<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->travelTo(now()->setDate(2026, 10, 1)->startOfDay());
    }

    private function order(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'customer_name' => 'Operations customer', 'phone' => '0912345678',
            'address' => 'Operations address', 'total_price' => 500000,
            'status' => 'cod_ordered', 'shipping_status' => 'pending',
            'inventory_status' => 'unreserved', 'is_demo' => false,
        ], $attributes));
    }

    public function test_displayed_order_reference_can_be_pasted_into_both_searches(): void
    {
        $target = $this->order();
        $this->order(['customer_name' => 'Other customer']);
        $search = '#DH'.str_pad($target->id, 5, '0', STR_PAD_LEFT);

        foreach (['admin.orders.index', 'admin.finance.transactions'] as $route) {
            $response = $this->get(route($route, ['search' => $search]))->assertOk();
            $this->assertSame([$target->id], $response->viewData('orders')->pluck('id')->all());
        }
        Http::assertNothingSent();
    }

    public function test_order_form_preserves_existing_cod_and_intermediate_shipping_statuses(): void
    {
        $order = $this->order(['shipping_status' => 'transporting']);
        $this->get(route('admin.orders.show', $order))->assertOk()
            ->assertSee('Giữ nguyên — Chờ thu COD')
            ->assertSee('Giữ nguyên — Đang trung chuyển');

        $this->patch(route('admin.orders.update', $order), ['status' => '', 'shipping_status' => ''])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('cod_ordered', $order->fresh()->status);
        $this->assertSame('transporting', $order->fresh()->shipping_status);
    }

    public function test_unpaid_online_orders_do_not_display_cod_collection_amount(): void
    {
        $order = $this->order(['status' => 'pending']);
        $order->paymentTransactions()->create(['gateway' => 'momo', 'status' => 'pending', 'amount' => 500000]);
        $this->get(route('admin.orders.index'))->assertOk()->assertSee('Ví MoMo')->assertDontSee('COD cần thu:');
    }

    public function test_new_order_items_are_counted_in_category_filters_and_revenue(): void
    {
        $category = Category::create(['name' => 'Operations category', 'slug' => 'operations-category']);
        $product = Perfume::create(['name' => 'Operations perfume', 'slug' => 'operations-perfume',
            'brand' => 'Test', 'gender' => 'unisex', 'volume_ml' => 100, 'price' => 250000,
            'stock' => 10, 'is_active' => true, 'category_id' => $category->id]);
        $order = $this->order(['status' => 'cod_paid']);
        $item = $order->items()->create(['perfume_id' => $product->id, 'quantity' => 2, 'price' => 250000]);
        $this->assertNull(DB::table('order_items')->where('id', $item->id)->value('product_id'));

        $response = $this->get(route('admin.reports.index', ['category_id' => $category->id]))->assertOk();
        $rows = $response->viewData('categoryRevenue');
        $this->assertCount(1, $rows);
        $this->assertEquals(500000, $rows->first()->total_revenue);
        $this->assertEquals(2, $rows->first()->total_qty);
        $this->assertEquals(500000, $response->viewData('totalRevenue'));
    }

    public function test_report_customer_total_includes_both_customer_roles_and_excludes_staff(): void
    {
        User::factory()->create(['role' => 'user']);
        User::factory()->create(['role' => 'customer']);
        User::factory()->create(['role' => 'livestream_staff']);
        User::factory()->create(['role' => 'admin']);

        $this->get(route('admin.reports.index', ['date_from' => '2024-01-01', 'date_to' => '2024-01-31']))
            ->assertOk()
            ->assertViewHas('totalCustomers', 2);
    }

    public function test_all_return_states_are_excluded_while_unshipped_paid_orders_are_included(): void
    {
        foreach (['return', 'returning', 'return_transporting', 'return_sorting', 'returned', 'cancelled'] as $shipping) {
            $this->order(['status' => 'cod_paid', 'shipping_status' => $shipping]);
        }
        $this->order(['status' => 'cod_paid', 'shipping_status' => 'not_shipped']);

        $response = $this->get(route('admin.reports.index'))->assertOk();
        $this->assertEquals(500000, $response->viewData('totalRevenue'));
    }

    public function test_chart_windows_follow_historical_filters_and_demo_payment_method(): void
    {
        $order = $this->order(['is_demo' => true, 'status' => 'completed', 'shipping_status' => 'delivered']);
        $order->forceFill(['created_at' => '2024-02-10 12:00:00'])->save();
        $order->paymentTransactions()->create(['gateway' => 'demo', 'status' => 'paid', 'amount' => 500000]);
        $response = $this->get(route('admin.reports.charts', [
            'mode' => 'demo', 'date_from' => '2024-01-01', 'date_to' => '2024-03-31',
        ]))->assertOk();

        $this->assertSame(['01/2024', '02/2024', '03/2024'], $response->viewData('revMonthLabels'));
        $this->assertSame([0.0, 500000.0, 0.0], $response->viewData('revMonthData'));
        $this->assertCount(90, $response->viewData('revDateLabels'));
        $this->assertSame('2024-03-31', \Illuminate\Support\Arr::last($response->viewData('revDateLabels')));
        $this->assertSame(['Mô phỏng'], $response->viewData('paymentMethodLabels'));
        $this->assertSame([500000.0], $response->viewData('paymentMethodRevenue'));
        $response->assertSee('name="mode"', false);
    }

    public function test_last_month_preset_does_not_overflow_at_end_of_month(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 31));
        $response = $this->get(route('admin.reports.index', ['preset' => 'last_month']))->assertOk();
        $this->assertSame('2026-02-01', $response->viewData('filters')['date_from']);
        $this->assertSame('2026-02-28', $response->viewData('filters')['date_to']);
        $response->assertDontSee('type="hidden" name="preset"', false);
    }
}
