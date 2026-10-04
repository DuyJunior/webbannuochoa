<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentStatusEvent;
use App\Models\PaymentTransaction;
use App\Services\FinancePaymentPolicy;
use App\Services\OrderEmailService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    private const PAYMENT_PRIORITY = "CASE WHEN status IN ('paid', 'refund_pending', 'refunded') THEN 0 ELSE 1 END";

    /** One financial record per order, even if an online payment was retried. */
    private function ordersQuery(): Builder
    {
        $paymentId = DB::table('payment_transactions')->select('id')->whereColumn('order_id', 'orders.id')
            ->orderByRaw(self::PAYMENT_PRIORITY)->orderByDesc('id')->limit(1);

        $source = DB::table('orders')->leftJoin('payment_transactions as payment', function ($join) use ($paymentId) {
            $join->on('payment.order_id', '=', 'orders.id')->where('payment.id', '=', $paymentId);
        })->select('orders.*', 'payment.id as payment_id', 'payment.paid_at as payment_paid_at')
            ->selectRaw("CASE WHEN payment.id IS NOT NULL THEN CASE WHEN payment.gateway IN ('cod', 'sepay', 'momo', 'demo') THEN payment.gateway ELSE 'unknown' END
                WHEN orders.status IN ('cod_ordered', 'cod_paid') THEN 'cod'
                WHEN orders.status IN ('paid', 'paid_momo') THEN 'momo' ELSE 'unknown' END as gateway")
            ->selectRaw("COALESCE(payment.status, CASE WHEN orders.status IN ('paid', 'paid_momo', 'cod_paid', 'completed') THEN 'paid'
                WHEN orders.status IN ('cancelled', 'failed', 'refund_pending', 'refunded', 'initiated') THEN orders.status ELSE 'pending' END) as payment_status");

        return Order::query()->fromSub($source, 'orders');
    }

    private function parseFilters(Request $request): array
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'min_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'max_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99', ...($request->filled('min_amount') ? ['gte:min_amount'] : [])],
            'gateway' => ['nullable', Rule::in(array_keys(FinancePaymentPolicy::GATEWAY_LABELS))],
            'payment_status' => ['nullable', Rule::in(array_keys(FinancePaymentPolicy::STATUS_LABELS))],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'amount_asc', 'amount_desc'])],
            'mode' => ['nullable', Rule::in(['real', 'demo'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'date_to.after_or_equal' => __('Ngày kết thúc phải từ ngày bắt đầu trở đi.'),
            '*.date_format' => __('Ngày lọc không hợp lệ.'),
            'max_amount.gte' => __('Số tiền tối đa phải lớn hơn hoặc bằng số tiền tối thiểu.'),
        ]);

        $filters['mode'] = $filters['mode'] ?? 'real';
        $filters['sort'] = $filters['sort'] ?? 'newest';

        return $filters;
    }

    private function filteredOrders(array $filters): Builder
    {
        $query = $this->ordersQuery()->where('orders.is_demo', $filters['mode'] === 'demo')
            ->where('orders.created_at', '<=', now());
        if ($filters['mode'] !== 'demo') {
            $query->where('gateway', '!=', 'demo');
        }
        if (isset($filters['search']) && trim($filters['search']) !== '') {
            $search = trim($filters['search']);
            $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('customer_name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
                if (preg_match('/^#?(?:DH)?0*(\d+)$/i', $search, $matches)) {
                    $query->orWhere('orders.id', $matches[1]);
                }
            });
        }
        foreach (['gateway', 'payment_status'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<', Carbon::parse($filters['date_to'])->addDay()->startOfDay());
        }
        if (isset($filters['min_amount'])) {
            $query->where('total_price', '>=', $filters['min_amount']);
        }
        if (isset($filters['max_amount'])) {
            $query->where('total_price', '<=', $filters['max_amount']);
        }

        return $query;
    }

    private function sortedOrders(Builder $query, array $filters): Builder
    {
        [$column, $direction] = match ($filters['sort']) {
            'oldest' => ['created_at', 'asc'],
            'amount_asc' => ['total_price', 'asc'],
            'amount_desc' => ['total_price', 'desc'],
            default => ['created_at', 'desc'],
        };

        return $query->orderBy($column, $direction)->orderBy('id', $direction);
    }

    private function pageData(Request $request): array
    {
        $filters = $this->parseFilters($request);
        $query = $this->filteredOrders($filters);
        // Aggregate before pagination; all cards and charts use the same filtered source.
        $totals = (clone $query)->selectRaw('COUNT(*) as order_count, COALESCE(SUM(total_price), 0) as total_amount')->first();
        $statusTotals = (clone $query)->select('payment_status')
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(total_price), 0) as total_amount')
            ->groupBy('payment_status')->get()->keyBy('payment_status');
        $methodTotals = (clone $query)->select('gateway')
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(total_price), 0) as total_amount')
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END), 0) as paid_amount")
            ->groupBy('gateway')->get()->keyBy('gateway');
        $orders = $this->sortedOrders($query, $filters)->paginate(15)->withQueryString();
        $paymentLabels = FinancePaymentPolicy::STATUS_LABELS;
        $gatewayLabels = FinancePaymentPolicy::GATEWAY_LABELS;

        return compact('filters', 'totals', 'statusTotals', 'methodTotals', 'orders', 'paymentLabels', 'gatewayLabels');
    }

    public function index(Request $request): View
    {
        return view('admin.finance.index', $this->pageData($request));
    }

    public function transactions(Request $request): View
    {
        $data = $this->pageData($request);
        $data['auditEvents'] = PaymentStatusEvent::query()->whereIn('order_id', $data['orders']->modelKeys())
            ->orderByDesc('id')->get()->groupBy('order_id');

        return view('admin.finance.transactions', $data);
    }

    public function sepayReceipts(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:64'],
            'result' => ['nullable', Rule::in(['paid', 'unmatched', 'amount_mismatch', 'duplicate_payment', 'late_payment', 'demo_order', 'order_not_payable'])],
        ]);
        $query = DB::table('sepay_webhook_receipts as receipt')
            ->leftJoin('payment_transactions as payment', 'payment.id', '=', 'receipt.payment_transaction_id')
            ->leftJoin('orders', 'orders.id', '=', 'payment.order_id')
            ->select('receipt.*', 'payment.order_id', 'payment.amount as expected_amount', 'payment.status as payment_status', 'orders.ghn_order_code', 'orders.status as order_status');
        if (! empty($filters['result'])) {
            $query->where('receipt.result', $filters['result']);
        }
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(fn ($q) => $q->where('receipt.provider_id', $search)->orWhere('receipt.payment_code', $search));
        }
        $receipts = $query->orderByDesc('receipt.id')->paginate(25)->withQueryString();

        return view('admin.finance.sepay', compact('receipts', 'filters'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->parseFilters($request);
        $query = $this->sortedOrders($this->filteredOrders($filters), $filters);

        return response()->streamDownload(function () use ($query) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Mã đơn', 'Mã thanh toán', 'Ngày tạo', 'Giá trị đơn (VND)', 'Phương thức', __('Trạng thái thanh toán'), 'Trạng thái đơn', 'Ngày thu tiền', 'Dữ liệu']);
            foreach ($query->cursor() as $order) {
                $cells = [$order->id, $order->payment_id, $order->created_at?->format('Y-m-d H:i:s'), $order->total_price,
                    $order->gateway, $order->payment_status, $order->status, $order->payment_paid_at, $order->is_demo ? 'DEMO' : 'REAL'];
                // Customer details, gateway payloads and refund references are intentionally omitted.
                $cells = array_map(fn ($value) => is_string($value) && preg_match('/^[\s]*[=+\-@]/u', $value) ? "'".$value : $value, $cells);
                fputcsv($file, $cells);
            }
            fclose($file);
        }, 'tai-chinh-'.$filters['mode'].'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $input = $request->validate([
            'payment_status' => ['required', Rule::in(array_keys(FinancePaymentPolicy::STATUS_LABELS))],
            'current_payment_status' => ['required', 'string', 'max:50'],
            'current_order_status' => ['required', 'string', 'max:50'],
            'current_payment_id' => ['required', 'integer', 'min:0'],
            'manual_refund_reference' => ['nullable', 'string', 'min:3', 'max:120', 'not_regex:/[\x00-\x1F\x7F]/u'],
            'mode' => ['nullable', Rule::in(['real', 'demo'])],
        ]);

        $changed = DB::transaction(function () use ($request, $order, $input): bool {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->is_demo !== (($input['mode'] ?? 'real') === 'demo')) {
                throw ValidationException::withMessages(['mode' => __('Loại dữ liệu không khớp. Hãy tải lại đúng trang giao dịch thực hoặc mô phỏng.')]);
            }
            $payment = $order->paymentTransactions()->orderByRaw(self::PAYMENT_PRIORITY)->orderByDesc('id')->lockForUpdate()->first();
            $currentStatus = $payment?->status ?? FinancePaymentPolicy::status($order);
            $gateway = $payment?->gateway ?? FinancePaymentPolicy::gateway($order);
            if ($gateway !== 'cod' && ! ($gateway === 'sepay' && in_array($input['payment_status'], ['refund_pending', 'refunded'], true))) {
                throw ValidationException::withMessages(['payment_status' => __('Chỉ được đối soát thủ công giao dịch COD. Giao dịch trực tuyến cần xác nhận từ cổng thanh toán.')]);
            }
            $reference = isset($input['manual_refund_reference']) ? trim($input['manual_refund_reference']) : null;
            if ($reference === '') {
                $reference = null;
            }
            $fingerprint = hash('sha256', json_encode([
                $order->id, $request->user()->id, (int) $input['current_payment_id'], $input['current_payment_status'],
                $input['current_order_status'], $input['payment_status'], $reference, $order->is_demo,
            ], JSON_THROW_ON_ERROR));

            $stale = (int) ($payment?->id ?? 0) !== (int) $input['current_payment_id']
                || $currentStatus !== $input['current_payment_status'] || $order->status !== $input['current_order_status'];
            if ($stale) {
                // A double click/retry is harmless only while its exact result is still current.
                $lastEvent = PaymentStatusEvent::query()->where('order_id', $order->id)->latest('id')->first();
                if ($lastEvent && hash_equals($lastEvent->request_fingerprint, $fingerprint)
                    && $lastEvent->payment_id === $payment?->id && $lastEvent->to_status === $currentStatus
                    && $lastEvent->order_status_after === $order->status) {
                    return false;
                }
                throw ValidationException::withMessages(['payment_status' => __('Giao dịch hoặc đơn hàng đã thay đổi. Hãy tải lại trang trước khi cập nhật.')]);
            }

            $newStatus = $input['payment_status'];
            if ($newStatus === $currentStatus) {
                return false;
            }
            if (! in_array($newStatus, FinancePaymentPolicy::allowedTransitions($order, $currentStatus, $gateway), true)) {
                throw ValidationException::withMessages(['payment_status' => __('Không được chuyển sang trạng thái này. Không thu tiền đơn đã hủy hoặc đang/đã hoàn hàng; không mở lại giao dịch đã kết thúc.')]);
            }
            if ($newStatus === 'refunded' && ! $order->is_demo && ($reference === null || mb_strlen($reference) < 3)) {
                throw ValidationException::withMessages(['manual_refund_reference' => __('Nhập mã chứng từ hoàn tiền thực tế (3–120 ký tự). Thao tác này chỉ ghi nhận, không chuyển tiền.')]);
            }

            $oldOrderStatus = $order->status;
            if (! $payment) {
                $payment = new PaymentTransaction(['order_id' => $order->id, 'gateway' => 'cod', 'amount' => $order->total_price]);
            }
            $payment->status = $newStatus;
            $payment->message = 'Finance '.strtoupper($gateway).': '.$currentStatus.' -> '.$newStatus.'; admin #'.$request->user()->id
                .($newStatus === 'refunded' ? '; manual refund recorded (no transfer)' : '');
            if ($newStatus === 'paid' && ! $payment->paid_at) {
                $payment->paid_at = now();
            }
            $payment->save();

            // Collection updates only the payment marker; completion and fulfilment stay intact.
            if ($newStatus === 'paid' && in_array($order->status, ['pending', 'confirmed', 'cod_ordered'], true)) {
                $order->update(['status' => 'cod_paid']);
            }
            PaymentStatusEvent::create([
                'order_id' => $order->id, 'payment_id' => $payment->id, 'actor_id' => $request->user()->id,
                'actor_name' => $request->user()->name, 'from_status' => $currentStatus, 'to_status' => $newStatus,
                'order_status_before' => $oldOrderStatus, 'order_status_after' => $order->status,
                'manual_refund_reference' => $newStatus === 'refunded' ? $reference : null,
                'request_fingerprint' => $fingerprint, 'created_at' => now(),
            ]);
            if ($newStatus === 'paid') {
                app(OrderEmailService::class)->paid($order);
            }

            return true;
        }, 3);

        return back()->with('success', $changed
            ? ($input['payment_status'] === 'refunded' ? __('Đã ghi nhận hoàn tiền thủ công. Hệ thống không chuyển tiền.') : __('Đã cập nhật và lưu lịch sử đối soát.'))
            : __('Trạng thái đã được ghi nhận; không có thay đổi mới.'));
    }
}
