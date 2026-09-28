<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Perfume;
use App\Models\User;
use App\Services\OrderInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        config(['demo.enabled' => false]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        return $admin;
    }

    private function order(array $extra = []): Order
    {
        return Order::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'customer_name' => 'Private Finance Customer',
            'phone' => '0987654321',
            'address' => 'Private Finance Street',
            'total_price' => 1000000,
            'status' => 'cod_ordered',
            'shipping_status' => 'pending',
            'inventory_status' => 'unreserved',
            'is_demo' => false,
        ], $extra));
    }

    private function payment(Order $order, array $extra = []): PaymentTransaction
    {
        return $order->paymentTransactions()->create(array_merge([
            'gateway' => 'cod',
            'amount' => $order->total_price,
            'status' => 'pending',
        ], $extra));
    }

    private function change(Order $order, ?PaymentTransaction $payment, string $target, array $extra = []): TestResponse
    {
        return $this->patch(route('admin.finance.update-status', $order), array_merge([
            'payment_status' => $target,
            'current_payment_status' => $payment?->status ?? 'pending',
            'current_payment_id' => $payment?->id ?? 0,
            'current_order_status' => $order->status,
            'mode' => $order->is_demo ? 'demo' : 'real',
        ], $extra));
    }

    private function ids(TestResponse $response): array
    {
        $response->assertOk();

        return $response->viewData('orders')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function transactions(array $filters = []): TestResponse
    {
        return $this->get(route('admin.finance.transactions', $filters));
    }

    public function test_finance_pages_exports_and_mutations_require_an_admin(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);

        foreach (['admin.finance.index', 'admin.finance.transactions', 'admin.finance.export'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
        $this->change($order, $payment, 'paid')->assertRedirect(route('login'));

        foreach (['user', 'livestream_staff'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            foreach (['admin.finance.index', 'admin.finance.transactions', 'admin.finance.export'] as $route) {
                $this->get(route($route))->assertRedirect(route('home'));
            }
            $this->change($order, $payment, 'paid')->assertRedirect(route('home'));
        }

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('payment_status_events', 0);
        $this->admin();
        $this->get(route('admin.finance.index'))->assertOk();
        $this->transactions()->assertOk();
        $this->get(route('admin.finance.export'))->assertOk();
    }

    public function test_real_orders_are_the_default_and_demo_totals_are_isolated(): void
    {
        $real = $this->order(['total_price' => 120000]);
        $this->payment($real, ['status' => 'paid']);
        $demo = $this->order(['is_demo' => true, 'total_price' => 990000]);
        $this->payment($demo, ['gateway' => 'demo', 'status' => 'paid']);
        $incorrectlyMarkedDemo = $this->order(['total_price' => 700000]);
        $this->payment($incorrectlyMarkedDemo, ['gateway' => 'demo', 'status' => 'paid']);
        $this->admin();

        // Deployment demo mode must not silently switch the finance ledger.
        config(['demo.enabled' => true]);
        $realResponse = $this->transactions();
        $this->assertSame([$real->id], $this->ids($realResponse));
        $this->assertEquals(120000, $realResponse->viewData('totals')->total_amount);
        $demoResponse = $this->transactions(['mode' => 'demo']);
        $this->assertSame([$demo->id], $this->ids($demoResponse));
        $this->assertEquals(990000, $demoResponse->viewData('totals')->total_amount);
    }

    public function test_representative_payment_prefers_money_states_then_latest_id_without_double_counting(): void
    {
        $paid = $this->order(['total_price' => 100000]);
        $this->payment($paid, ['status' => 'paid', 'gateway' => 'momo']);
        $this->payment($paid, ['status' => 'failed', 'gateway' => 'cod']);

        $refunded = $this->order(['total_price' => 200000]);
        $this->payment($refunded, ['status' => 'paid']);
        $this->payment($refunded, ['status' => 'refund_pending']);
        $this->payment($refunded, ['status' => 'refunded']);
        $this->payment($refunded, ['status' => 'pending']);

        $failed = $this->order(['total_price' => 300000]);
        $this->payment($failed, ['status' => 'pending']);
        $this->payment($failed, ['status' => 'failed']);
        $this->admin();

        $response = $this->transactions();
        $this->assertEquals(3, $response->assertOk()->viewData('totals')->order_count);
        $this->assertEquals(600000, $response->viewData('totals')->total_amount);
        $this->assertCount(3, $response->viewData('orders'));
        $this->assertSame([$paid->id], $this->ids($this->transactions(['payment_status' => 'paid', 'gateway' => 'momo'])));
        $this->assertSame([$refunded->id], $this->ids($this->transactions(['payment_status' => 'refunded'])));
        $this->assertSame([$failed->id], $this->ids($this->transactions(['payment_status' => 'failed'])));
        $this->assertSame([], $this->ids($this->transactions(['payment_status' => 'pending'])));
    }

    public function test_overview_and_transactions_use_identical_filtered_totals(): void
    {
        $included = $this->order(['total_price' => 125000]);
        $this->payment($included, ['status' => 'paid']);
        $this->payment($this->order(['total_price' => 500000]), ['status' => 'failed']);
        $this->admin();
        $filters = ['gateway' => 'cod', 'payment_status' => 'paid', 'max_amount' => 200000];

        $overview = $this->get(route('admin.finance.index', $filters))->assertOk();
        $ledger = $this->transactions($filters)->assertOk();
        foreach ([$overview, $ledger] as $response) {
            $this->assertEquals(1, $response->viewData('totals')->order_count);
            $this->assertEquals(125000, $response->viewData('totals')->total_amount);
            $this->assertEquals(125000, $response->viewData('statusTotals')->get('paid')->total_amount);
            $this->assertEquals(125000, $response->viewData('methodTotals')->get('cod')->total_amount);
        }
    }

    public function test_legacy_orders_are_included_once_with_inferred_payment_states(): void
    {
        $pending = $this->order(['status' => 'cod_ordered']);
        $paid = $this->order(['status' => 'cod_paid']);
        $momo = $this->order(['status' => 'paid_momo']);
        $this->admin();

        $this->assertSame([$pending->id], $this->ids($this->transactions(['gateway' => 'cod', 'payment_status' => 'pending'])));
        $this->assertSame([$paid->id], $this->ids($this->transactions(['gateway' => 'cod', 'payment_status' => 'paid'])));
        $this->assertSame([$momo->id], $this->ids($this->transactions(['gateway' => 'momo', 'payment_status' => 'paid'])));
        $this->assertEquals(3, $this->transactions()->assertOk()->viewData('totals')->order_count);
    }

    public function test_future_orders_are_excluded_from_both_ledgers_all_summaries_and_csv(): void
    {
        $this->travelTo(now()->startOfSecond());
        $expected = [];
        foreach (['real' => false, 'demo' => true] as $mode => $isDemo) {
            $gateway = $isDemo ? 'demo' : 'cod';
            $current = $this->order(['is_demo' => $isDemo, 'total_price' => 100000]);
            $this->payment($current, ['gateway' => $gateway, 'status' => 'paid']);
            $future = $this->order(['is_demo' => $isDemo, 'total_price' => 900000]);
            $future->forceFill(['created_at' => now()->addSecond()])->save();
            $this->payment($future, ['gateway' => $gateway, 'status' => 'paid']);
            $expected[$mode] = [$current->id, $gateway];
        }
        $this->admin();

        foreach ($expected as $mode => [$id, $gateway]) {
            // A user-supplied future end date must not bypass the ledger's current-time limit.
            $filters = ['mode' => $mode, 'date_to' => now()->addDay()->toDateString()];
            foreach (['admin.finance.index', 'admin.finance.transactions'] as $route) {
                $response = $this->get(route($route, $filters));
                $this->assertSame([$id], $this->ids($response));
                $this->assertEquals(1, $response->viewData('totals')->order_count);
                $this->assertEquals(100000, $response->viewData('totals')->total_amount);
                $this->assertEquals(100000, $response->viewData('statusTotals')->get('paid')->total_amount);
                $this->assertEquals(100000, $response->viewData('methodTotals')->get($gateway)->total_amount);
                $this->assertEquals(100000, $response->viewData('methodTotals')->get($gateway)->paid_amount);
            }
            $csv = $this->get(route('admin.finance.export', $filters))->assertOk()->streamedContent();
            $rows = array_map('str_getcsv', preg_split('/\r?\n/', trim($csv)));
            $this->assertCount(2, $rows);
            $this->assertSame($id, (int) $rows[1][0]);
        }
    }

    public function test_method_paid_totals_exclude_unpaid_and_refund_states_using_the_representative_payment(): void
    {
        foreach ([['cod', 'paid', 100000], ['momo', 'paid', 200000], ['cod', 'pending', 300000],
            ['cod', 'refund_pending', 400000], ['momo', 'refunded', 500000], ['momo', 'failed', 600000]] as [$gateway, $status, $amount]) {
            $order = $this->order(['total_price' => $amount]);
            if (in_array($status, ['refund_pending', 'refunded'], true)) {
                $this->payment($order, ['gateway' => $gateway, 'status' => 'paid']);
            }
            $this->payment($order, ['gateway' => $gateway, 'status' => $status]);
            if ($status === 'paid') {
                $this->payment($order, ['gateway' => $gateway, 'status' => 'failed']);
            }
        }
        $this->admin();

        foreach (['admin.finance.index', 'admin.finance.transactions'] as $route) {
            $response = $this->get(route($route))->assertOk();
            $methods = $response->viewData('methodTotals');
            $this->assertEquals(100000, $methods->get('cod')->paid_amount);
            $this->assertEquals(200000, $methods->get('momo')->paid_amount);
            $this->assertEquals(800000, $methods->get('cod')->total_amount);
            $this->assertEquals(1300000, $methods->get('momo')->total_amount);
            $this->assertEquals(3, $methods->get('cod')->order_count);
            $this->assertEquals(3, $methods->get('momo')->order_count);

            $filtered = $this->get(route($route, ['gateway' => 'cod', 'payment_status' => 'refund_pending']))->assertOk();
            $filteredMethods = $filtered->viewData('methodTotals');
            $this->assertCount(1, $filteredMethods);
            $this->assertEquals(0, $filteredMethods->get('cod')->paid_amount);
            $this->assertEquals(400000, $filteredMethods->get('cod')->total_amount);
        }
    }

    public function test_search_accepts_customer_name_phone_and_prefixed_order_id(): void
    {
        $target = $this->order(['customer_name' => 'Searchable Buyer', 'phone' => '0900112233']);
        $this->payment($target);
        $this->payment($this->order(['customer_name' => 'Different Buyer', 'phone' => '0999887766']));
        $this->admin();

        foreach (['Searchable', '0900112233', '#'.$target->id, 'DH'.str_pad((string) $target->id, 6, '0', STR_PAD_LEFT)] as $search) {
            $this->assertSame([$target->id], $this->ids($this->transactions(['search' => $search])));
        }
        $this->assertSame([], $this->ids($this->transactions(['search' => 'no such customer'])));
    }

    public function test_date_amount_gateway_and_status_filters_work_together_with_inclusive_bounds(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 28)->startOfDay());
        $expected = [];
        foreach ([['2026-09-10 00:00:00', 100000], ['2026-09-10 23:59:59', 200000],
            ['2026-09-09 23:59:59', 150000], ['2026-09-11 00:00:00', 150000],
            ['2026-09-10 12:00:00', 99999], ['2026-09-10 12:00:00', 200001]] as $index => [$created, $amount]) {
            $order = $this->order(['total_price' => $amount]);
            $order->forceFill(['created_at' => $created])->save();
            $this->payment($order, ['status' => 'paid']);
            if ($index < 2) {
                $expected[] = $order->id;
            }
        }
        foreach ([['momo', 'paid'], ['cod', 'failed']] as [$gateway, $status]) {
            $order = $this->order(['total_price' => 150000]);
            $order->forceFill(['created_at' => '2026-09-10 12:00:00'])->save();
            $this->payment($order, ['gateway' => $gateway, 'status' => $status]);
        }
        $this->admin();

        $response = $this->transactions(['date_from' => '2026-09-10', 'date_to' => '2026-09-10',
            'min_amount' => 100000, 'max_amount' => 200000, 'gateway' => 'cod', 'payment_status' => 'paid', 'sort' => 'oldest']);
        $this->assertSame($expected, $this->ids($response));
        $this->assertEquals(300000, $response->viewData('totals')->total_amount);
    }

    public function test_finance_filters_are_validated_on_both_pages_and_export(): void
    {
        $this->admin();
        $invalid = [
            [['date_from' => '2026-02-30'], 'date_from'],
            [['date_from' => '2026-09-20', 'date_to' => '2026-09-19'], 'date_to'],
            [['min_amount' => -1], 'min_amount'],
            [['max_amount' => 'money'], 'max_amount'],
            [['min_amount' => 200, 'max_amount' => 100], 'max_amount'],
            [['gateway' => 'forged'], 'gateway'],
            [['payment_status' => 'completed'], 'payment_status'],
            [['mode' => 'all'], 'mode'],
            [['sort' => 'total_price desc; drop table orders'], 'sort'],
            [['search' => str_repeat('a', 101)], 'search'],
            [['page' => 0], 'page'],
        ];

        foreach (['admin.finance.index', 'admin.finance.transactions', 'admin.finance.export'] as $route) {
            foreach ($invalid as [$filter, $field]) {
                $this->getJson(route($route, $filter))->assertUnprocessable()->assertJsonValidationErrors($field);
            }
        }
    }

    public function test_status_and_method_summaries_cover_all_filtered_rows_and_pagination_preserves_filters(): void
    {
        for ($i = 0; $i < 18; $i++) {
            $order = $this->order(['customer_name' => 'Ledger Match', 'total_price' => 100000]);
            $this->payment($order, ['status' => $i < 10 ? 'paid' : 'pending', 'gateway' => $i < 10 ? 'momo' : 'cod']);
        }
        $this->payment($this->order(['customer_name' => 'Outside filter', 'total_price' => 999999]));
        $this->admin();

        $response = $this->transactions(['search' => 'Ledger Match', 'sort' => 'amount_asc', 'mode' => 'real']);
        $response->assertOk();
        $orders = $response->viewData('orders');
        $this->assertCount(15, $orders);
        $this->assertSame(18, $orders->total());
        $this->assertEquals(1800000, $response->viewData('totals')->total_amount);
        $this->assertEquals(10, $response->viewData('statusTotals')->get('paid')->order_count);
        $this->assertEquals(800000, $response->viewData('statusTotals')->get('pending')->total_amount);
        $this->assertEquals(1000000, $response->viewData('methodTotals')->get('momo')->total_amount);
        $this->assertEquals(8, $response->viewData('methodTotals')->get('cod')->order_count);
        parse_str(parse_url($orders->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertSame('Ledger Match', $query['search']);
        $this->assertSame('amount_asc', $query['sort']);
        $this->assertSame('real', $query['mode']);
        $this->assertSame('2', $query['page']);
        $secondPage = $this->transactions($query)->assertOk();
        $this->assertCount(3, $secondPage->viewData('orders'));
        $this->assertEquals(1800000, $secondPage->viewData('totals')->total_amount);
        $this->assertEmpty(array_intersect($orders->pluck('id')->all(), $secondPage->viewData('orders')->pluck('id')->all()));
    }

    public function test_supported_sorts_order_the_filtered_ledger(): void
    {
        $low = $this->order(['total_price' => 100000]);
        $high = $this->order(['total_price' => 300000]);
        $middle = $this->order(['total_price' => 200000]);
        foreach ([$low, $high, $middle] as $index => $order) {
            $order->forceFill(['created_at' => now()->subDays(3 - $index)])->save();
            $this->payment($order);
        }
        $this->admin();

        foreach ([
            'newest' => [$middle->id, $high->id, $low->id],
            'oldest' => [$low->id, $high->id, $middle->id],
            'amount_asc' => [$low->id, $middle->id, $high->id],
            'amount_desc' => [$high->id, $middle->id, $low->id],
        ] as $sort => $expected) {
            $this->assertSame($expected, $this->ids($this->transactions(['sort' => $sort])));
        }
    }

    public function test_cod_collection_records_actor_and_paid_at_without_touching_shipping_or_inventory(): void
    {
        $order = $this->order(['shipping_status' => 'delivering']);
        $product = Perfume::create(['name' => 'Finance inventory fixture', 'slug' => (string) Str::uuid(),
            'brand' => 'Test', 'gender' => 'unisex', 'volume_ml' => 100, 'price' => 1000000, 'stock' => 10, 'is_active' => true]);
        $order->items()->create(['perfume_id' => $product->id, 'quantity' => 2, 'volume_ml' => 100, 'price' => 500000]);
        app(OrderInventoryService::class)->reserve($order);
        $payment = $this->payment($order);
        $movementCount = DB::table('inventory_movements')->count();
        $admin = $this->admin();

        $this->change($order, $payment, 'paid')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertSame('cod_paid', $order->fresh()->status);
        $this->assertSame('delivering', $order->fresh()->shipping_status);
        $this->assertSame('reserved', $order->fresh()->inventory_status);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame($movementCount, DB::table('inventory_movements')->count());
        $this->assertDatabaseHas('payment_status_events', ['order_id' => $order->id, 'payment_id' => $payment->id,
            'actor_id' => $admin->id, 'from_status' => 'pending', 'to_status' => 'paid']);
        $this->assertDatabaseCount('payment_transactions', 1);
        Http::assertNothingSent();
    }

    public function test_collecting_failed_cod_preserves_completed_order_and_delivery_state(): void
    {
        $order = $this->order(['status' => 'completed', 'shipping_status' => 'delivered', 'inventory_status' => 'reserved']);
        $payment = $this->payment($order, ['status' => 'failed']);
        $this->admin();

        $this->change($order, $payment, 'paid')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->shipping_status);
        $this->assertSame('reserved', $order->fresh()->inventory_status);
    }

    public function test_legacy_cod_collection_creates_one_payment_and_replay_does_not_duplicate_it(): void
    {
        $order = $this->order();
        $admin = $this->admin();

        $this->change($order, null, 'paid')->assertRedirect()->assertSessionHasNoErrors();
        $this->change($order, null, 'paid')->assertRedirect();
        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertDatabaseCount('payment_status_events', 1);
        $this->assertDatabaseHas('payment_transactions', ['order_id' => $order->id, 'gateway' => 'cod', 'status' => 'paid']);
        $this->assertDatabaseHas('payment_status_events', ['order_id' => $order->id, 'actor_id' => $admin->id]);
    }

    public function test_noop_and_identical_replay_leave_paid_at_and_audit_unchanged(): void
    {
        $this->travelTo(now()->startOfSecond());
        $order = $this->order();
        $payment = $this->payment($order);
        $this->admin();
        $this->change($order, $payment, 'paid')->assertRedirect()->assertSessionHasNoErrors();
        $paidAt = $payment->fresh()->paid_at->toDateTimeString();
        $this->travel(10)->minutes();

        $this->change($order, $payment, 'paid')->assertRedirect();
        $this->change($order->fresh(), $payment->fresh(), 'paid')->assertRedirect();
        $this->assertSame($paidAt, $payment->fresh()->paid_at->toDateTimeString());
        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertDatabaseCount('payment_status_events', 1);
    }

    public function test_finance_updates_the_representative_payment_without_modifying_later_failed_attempts(): void
    {
        $order = $this->order(['status' => 'cod_paid']);
        $paid = $this->payment($order, ['status' => 'paid', 'paid_at' => now()->subDay()]);
        $failedRetry = $this->payment($order, ['status' => 'failed']);
        $this->admin();

        $this->change($order, $failedRetry, 'paid')->assertSessionHasErrors('payment_status');
        $this->change($order, $paid, 'refund_pending')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('refund_pending', $paid->fresh()->status);
        $this->assertSame('failed', $failedRetry->fresh()->status);
        $this->assertDatabaseCount('payment_transactions', 2);
        $this->assertDatabaseCount('payment_status_events', 1);
        $this->assertDatabaseHas('payment_status_events', ['payment_id' => $paid->id, 'from_status' => 'paid', 'to_status' => 'refund_pending']);
    }

    public function test_retry_cycles_record_real_changes_even_if_the_same_transition_happens_again(): void
    {
        $order = $this->order();
        $payment = $this->payment($order, ['status' => 'failed']);
        $this->admin();

        foreach (['pending', 'failed', 'pending'] as $target) {
            $this->change($order->fresh(), $payment->fresh(), $target)->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($target, $payment->fresh()->status);
        }
        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertDatabaseCount('payment_status_events', 3);
        $this->assertSame(2, DB::table('payment_status_events')->where('from_status', 'failed')->where('to_status', 'pending')->count());
    }

    public function test_replaying_collection_after_a_refund_started_cannot_undo_the_refund(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $this->admin();
        $this->change($order, $payment, 'paid')->assertRedirect()->assertSessionHasNoErrors();
        $this->change($order->fresh(), $payment->fresh(), 'refund_pending')->assertRedirect()->assertSessionHasNoErrors();

        $this->change($order, $payment, 'paid')->assertRedirect()->assertSessionHasErrors('payment_status');
        $this->assertSame('refund_pending', $payment->fresh()->status);
        $this->assertDatabaseCount('payment_status_events', 2);
    }

    public function test_refund_workflow_requires_real_manual_reference_and_keeps_paid_at(): void
    {
        $order = $this->order(['status' => 'completed', 'shipping_status' => 'delivered']);
        $payment = $this->payment($order, ['status' => 'paid', 'paid_at' => now()->subDays(2)]);
        $paidAt = $payment->paid_at->toDateTimeString();
        $admin = $this->admin();

        $this->change($order, $payment, 'refund_pending')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('refund_pending', $payment->fresh()->status);
        $this->change($order->fresh(), $payment->fresh(), 'refunded')->assertSessionHasErrors('manual_refund_reference');
        $this->assertSame('refund_pending', $payment->fresh()->status);
        foreach (['x', str_repeat('x', 121)] as $reference) {
            $this->change($order->fresh(), $payment->fresh(), 'refunded', ['manual_refund_reference' => $reference])
                ->assertSessionHasErrors('manual_refund_reference');
        }
        $this->change($order->fresh(), $payment->fresh(), 'refunded', ['manual_refund_reference' => 'BANK-REFUND-20260928'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame($paidAt, $payment->fresh()->paid_at->toDateTimeString());
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->shipping_status);
        $this->assertDatabaseCount('payment_status_events', 2);
        $this->assertDatabaseHas('payment_status_events', ['payment_id' => $payment->id, 'actor_id' => $admin->id,
            'from_status' => 'refund_pending', 'to_status' => 'refunded', 'manual_refund_reference' => 'BANK-REFUND-20260928']);
        Http::assertNothingSent();
    }

    public function test_cancelled_order_can_record_refund_without_reopening_order(): void
    {
        $order = $this->order(['status' => 'cancelled', 'shipping_status' => 'cancelled', 'inventory_status' => 'released']);
        $payment = $this->payment($order, ['status' => 'refund_pending', 'paid_at' => now()->subDay()]);
        $this->admin();

        $this->change($order, $payment, 'refunded', ['manual_refund_reference' => 'MANUAL-CANCEL-REFUND'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->shipping_status);
        $this->assertSame('released', $order->fresh()->inventory_status);
    }

    public function test_finance_rejects_online_payment_edits_even_with_valid_stale_tokens(): void
    {
        $this->admin();
        foreach (['pending', 'paid', 'refund_pending'] as $status) {
            $order = $this->order();
            $payment = $this->payment($order, ['gateway' => 'momo', 'status' => $status]);
            $target = match ($status) {
                'pending' => 'paid',
                'paid' => 'refund_pending',
                default => 'refunded',
            };
            $this->change($order, $payment, $target, ['manual_refund_reference' => 'BANK-DO-NOT-EXECUTE'])
                ->assertRedirect()->assertSessionHasErrors('payment_status');
            $this->assertSame($status, $payment->fresh()->status);
            $this->assertSame('cod_ordered', $order->fresh()->status);
        }
        $this->assertDatabaseCount('payment_status_events', 0);
        Http::assertNothingSent();
    }

    public function test_stale_payment_status_order_status_or_representative_id_cannot_overwrite_current_state(): void
    {
        $this->admin();
        foreach (['payment_status', 'order_status', 'payment_id'] as $staleField) {
            $order = $this->order();
            $payment = $this->payment($order);
            if ($staleField === 'payment_status') {
                PaymentTransaction::whereKey($payment->id)->update(['status' => 'failed']);
            } elseif ($staleField === 'order_status') {
                Order::whereKey($order->id)->update(['status' => 'confirmed']);
            } else {
                $this->payment($order);
            }
            $before = $payment->fresh()->status;
            $beforeOrder = $order->fresh()->status;
            $this->change($order, $payment, 'paid')->assertRedirect()->assertSessionHasErrors('payment_status');
            $this->assertSame($before, $payment->fresh()->status);
            $this->assertSame($beforeOrder, $order->fresh()->status);
            $this->assertFalse($order->paymentTransactions()->where('status', 'paid')->exists());
        }
        $this->assertDatabaseCount('payment_status_events', 0);
    }

    public function test_all_cancelled_and_return_shipping_states_block_collecting_or_reopening_payment(): void
    {
        $this->admin();
        foreach (['cancelled', 'return', 'returning', 'return_transporting', 'return_sorting', 'returned'] as $shippingStatus) {
            foreach (['pending' => 'paid', 'failed' => 'pending'] as $from => $target) {
                $order = $this->order(['shipping_status' => $shippingStatus]);
                $payment = $this->payment($order, ['status' => $from]);
                $this->change($order, $payment, $target)->assertRedirect()->assertSessionHasErrors('payment_status');
                $this->assertSame($from, $payment->fresh()->status, $shippingStatus.' must block '.$target);
                $this->assertSame($shippingStatus, $order->fresh()->shipping_status);
            }
        }
        $cancelled = $this->order(['status' => 'cancelled', 'shipping_status' => 'pending']);
        $payment = $this->payment($cancelled);
        $this->change($cancelled, $payment, 'paid')->assertRedirect()->assertSessionHasErrors('payment_status');
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('payment_status_events', 0);
    }

    public function test_terminal_payment_states_and_direct_refund_skips_are_rejected(): void
    {
        $this->admin();
        foreach ([['paid', 'pending'], ['paid', 'refunded'], ['refund_pending', 'paid'],
            ['refunded', 'paid'], ['refunded', 'pending'], ['cancelled', 'paid'], ['cancelled', 'pending']] as [$from, $target]) {
            $order = $this->order();
            $payment = $this->payment($order, ['status' => $from]);
            $this->change($order, $payment, $target, ['manual_refund_reference' => 'MANUAL-INVALID-TRANSITION'])
                ->assertRedirect()->assertSessionHasErrors('payment_status');
            $this->assertSame($from, $payment->fresh()->status);
        }
        $this->assertDatabaseCount('payment_status_events', 0);
    }

    public function test_mutation_requires_all_concurrency_tokens_and_valid_values(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $this->admin();
        $payload = ['payment_status' => 'paid', 'current_payment_status' => 'pending',
            'current_payment_id' => $payment->id, 'current_order_status' => 'cod_ordered', 'mode' => 'real'];
        foreach (['payment_status', 'current_payment_status', 'current_payment_id', 'current_order_status'] as $field) {
            $missing = $payload;
            unset($missing[$field]);
            $this->patchJson(route('admin.finance.update-status', $order), $missing)
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        foreach (['current_payment_id' => -1, 'payment_status' => 'completed', 'mode' => 'all'] as $field => $value) {
            $this->patchJson(route('admin.finance.update-status', $order), array_replace($payload, [$field => $value]))
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('payment_status_events', 0);
    }

    public function test_posting_another_orders_payment_id_cannot_change_either_order(): void
    {
        $first = $this->order();
        $firstPayment = $this->payment($first);
        $second = $this->order();
        $secondPayment = $this->payment($second);
        $this->admin();

        $this->change($first, $firstPayment, 'paid', ['current_payment_id' => $secondPayment->id])
            ->assertRedirect()->assertSessionHasErrors('payment_status');
        $this->assertSame('pending', $firstPayment->fresh()->status);
        $this->assertSame('pending', $secondPayment->fresh()->status);
        $this->assertDatabaseCount('payment_status_events', 0);
    }

    public function test_mode_mismatch_cannot_edit_a_demo_order_through_real_ledger(): void
    {
        $order = $this->order(['is_demo' => true]);
        $payment = $this->payment($order, ['gateway' => 'demo']);
        $this->admin();

        $this->change($order, $payment, 'paid', ['mode' => 'real'])->assertRedirect()->assertSessionHasErrors('mode');
        $this->change($order, $payment, 'paid', ['mode' => 'demo'])->assertRedirect()->assertSessionHasErrors('payment_status');
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('payment_status_events', 0);
    }

    public function test_unknown_legacy_method_cannot_be_invented_as_a_cod_payment(): void
    {
        $order = $this->order(['status' => 'pending']);
        $this->admin();

        $this->change($order, null, 'paid')->assertRedirect()->assertSessionHasErrors('payment_status');
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertDatabaseCount('payment_transactions', 0);
        $this->assertDatabaseCount('payment_status_events', 0);
    }

    public function test_csv_export_uses_the_same_filters_all_pages_and_no_customer_or_gateway_payload_data(): void
    {
        $expected = [];
        for ($i = 0; $i < 17; $i++) {
            $order = $this->order(['total_price' => 100000 + $i]);
            $this->payment($order, ['status' => 'paid', 'transaction_id' => 'PRIVATE-GATEWAY-TRANSACTION',
                'request_payload' => ['secret' => 'PRIVATE-PAYLOAD-TOKEN'],
                'response_payload' => ['email' => 'private-finance@example.test']]);
            $expected[] = $order->id;
        }
        $excluded = $this->order(['total_price' => 999999]);
        $this->payment($excluded, ['status' => 'failed']);
        $demo = $this->order(['is_demo' => true]);
        $this->payment($demo, ['status' => 'paid', 'gateway' => 'demo']);
        $this->admin();

        $filters = ['gateway' => 'cod', 'payment_status' => 'paid', 'max_amount' => 100100, 'sort' => 'amount_asc', 'page' => 2];
        $ledger = $this->transactions($filters)->assertOk();
        $this->assertSame(17, $ledger->viewData('orders')->total());
        $response = $this->get(route('admin.finance.export', $filters))->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $rows = array_map('str_getcsv', preg_split('/\r?\n/', trim($csv)));
        $this->assertCount(18, $rows);
        $this->assertSame($expected, array_map(fn ($row) => (int) ltrim($row[0], '#'), array_slice($rows, 1)));
        foreach (['Private Finance Customer', '0987654321', 'Private Finance Street', 'PRIVATE-GATEWAY-TRANSACTION',
            'PRIVATE-PAYLOAD-TOKEN', 'private-finance@example.test'] as $privateValue) {
            $this->assertStringNotContainsString($privateValue, $csv);
        }
        Http::assertNothingSent();
    }

    public function test_export_escapes_spreadsheet_formula_values(): void
    {
        $formula = '=HYPERLINK("https://example.test","unsafe")';
        $order = $this->order(['status' => $formula]);
        $this->payment($order);
        $this->admin();

        $csv = $this->get(route('admin.finance.export'))->assertOk()->streamedContent();
        $rows = array_map('str_getcsv', preg_split('/\r?\n/', trim($csv)));
        $this->assertCount(2, $rows);
        $this->assertContains("'".$formula, $rows[1]);
        $this->assertNotContains($formula, $rows[1]);
    }
}
