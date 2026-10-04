<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Chuẩn hóa bộ lọc từ Request
     */
    private function parseFilters(Request $request): array
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'category_id' => 'nullable|integer|exists:categories,id',
            'gateway' => 'nullable|in:cod,sepay,momo,demo',
            'preset' => 'nullable|in:today,yesterday,7days,30days,this_month,last_month,this_year',
            'mode' => 'nullable|in:real,demo',
        ], [
            'date_to.after_or_equal' => __('Ngày kết thúc phải từ ngày bắt đầu trở đi.'),
            '*.date_format' => __('Ngày lọc không hợp lệ.'),
        ]);
        $preset = $request->get('preset', '');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if ($preset) {
            switch ($preset) {
                case 'today':
                    $dateFrom = Carbon::today()->toDateString();
                    $dateTo = Carbon::today()->toDateString();
                    break;
                case 'yesterday':
                    $dateFrom = Carbon::yesterday()->toDateString();
                    $dateTo = Carbon::yesterday()->toDateString();
                    break;
                case '7days':
                    $dateFrom = Carbon::today()->subDays(6)->toDateString();
                    $dateTo = Carbon::today()->toDateString();
                    break;
                case '30days':
                    $dateFrom = Carbon::today()->subDays(29)->toDateString();
                    $dateTo = Carbon::today()->toDateString();
                    break;
                case 'this_month':
                    $dateFrom = Carbon::today()->startOfMonth()->toDateString();
                    $dateTo = Carbon::today()->toDateString();
                    break;
                case 'last_month':
                    $dateFrom = Carbon::today()->startOfMonth()->subMonth()->toDateString();
                    $dateTo = Carbon::today()->startOfMonth()->subDay()->toDateString();
                    break;
                case 'this_year':
                    $dateFrom = Carbon::today()->startOfYear()->toDateString();
                    $dateTo = Carbon::today()->toDateString();
                    break;
            }
        }

        return [
            'mode' => $request->get('mode', 'real'),
            'preset' => $preset,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'category_id' => $request->get('category_id'),
            'gateway' => $request->get('gateway'),
        ];
    }

    private function paidOrders(array $filters = []): Builder
    {
        // Mỗi đơn chỉ tính một lần; ưu tiên giao dịch đã thu/hoàn tiền hơn lần thử mới.
        $paymentStatus = DB::table('payment_transactions')->select('status')
            ->whereColumn('order_id', 'orders.id')
            ->orderByRaw("CASE WHEN status IN ('paid', 'refund_pending', 'refunded') THEN 0 ELSE 1 END")
            ->orderByDesc('id')->limit(1);

        $query = Order::query()->where('orders.is_demo', ($filters['mode'] ?? 'real') === 'demo')->where('orders.created_at', '<=', now())
            ->where('orders.status', '!=', 'cancelled')
            ->where(fn (Builder $query) => $query->whereNull('orders.shipping_status')
                ->orWhereNotIn('orders.shipping_status', ['cancelled', 'return', 'returning', 'return_transporting', 'return_sorting', 'returned']))
            ->where(function (Builder $query) use ($paymentStatus) {
                $query->where($paymentStatus, 'paid')
                    ->orWhere(function (Builder $legacy) {
                        $legacy->whereDoesntHave('paymentTransactions')
                            ->whereIn('orders.status', ['paid', 'cod_paid', 'paid_momo', 'completed']);
                    });
            });

        // 1. Bộ lọc khoảng ngày tạo đơn
        if (! empty($filters['date_from'])) {
            $query->where('orders.created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (! empty($filters['date_to'])) {
            $query->where('orders.created_at', '<', Carbon::parse($filters['date_to'])->addDay()->startOfDay());
        }

        // 2. Bộ lọc cổng thanh toán (gateway: cod | sepay | momo (legacy))
        if (! empty($filters['gateway'])) {
            $g = $filters['gateway'];
            $gatewaySub = DB::table('payment_transactions')->select('gateway')
                ->whereColumn('order_id', 'orders.id')->where('status', 'paid')->orderByDesc('id')->limit(1);

            $query->where(function ($sub) use ($gatewaySub, $g) {
                $sub->where($gatewaySub, $g)
                    ->orWhere(function ($legacy) use ($g) {
                        $legacy->whereDoesntHave('paymentTransactions');
                        if ($g === 'cod') {
                            $legacy->where('orders.status', 'cod_paid');
                        } elseif ($g === 'momo') {
                            $legacy->whereIn('orders.status', ['paid', 'paid_momo']);
                        } else {
                            $legacy->whereRaw('1 = 0');
                        }
                    });
            });
        }

        // 3. Bộ lọc theo Danh mục sản phẩm (category_id)
        if (! empty($filters['category_id'])) {
            $catId = $filters['category_id'];
            $query->whereExists(function ($sub) use ($catId) {
                $sub->select(DB::raw(1))
                    ->from('order_items')
                    ->join('perfumes', 'order_items.perfume_id', '=', 'perfumes.id')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->where('perfumes.category_id', $catId);
            });
        }

        return $query;
    }

    private function categoryRevenue(array $filters = []): Collection
    {
        $query = DB::table('order_items')
            ->join('perfumes', 'order_items.perfume_id', '=', 'perfumes.id')
            ->leftJoin('categories', 'perfumes.category_id', '=', 'categories.id')
            ->whereIn('order_items.order_id', $this->paidOrders($filters)->select('orders.id'));

        if (! empty($filters['category_id'])) {
            $query->where('perfumes.category_id', $filters['category_id']);
        }

        return $query
            ->select('perfumes.category_id', 'categories.name as category_name')
            ->selectRaw('SUM(order_items.price * order_items.quantity) as total_revenue, SUM(order_items.quantity) as total_qty')
            ->groupBy('perfumes.category_id', 'categories.name')
            ->orderByDesc('total_revenue')
            ->get();
    }

    private function dailyRevenue(array $filters = []): Collection
    {
        return $this->paidOrders($filters)
            ->selectRaw('DATE(orders.created_at) as date, SUM(total_price) as total_revenue, COUNT(*) as order_count')
            ->groupByRaw('DATE(orders.created_at)')
            ->orderBy('date')
            ->get();
    }

    // Tổng hợp từ dữ liệu theo ngày, dùng được với cả MySQL và SQLite.
    private function periodRevenue(Collection $days, string $period): Collection
    {
        return $days->groupBy(fn ($day) => substr($day->date, 0, $period === 'month' ? 7 : 4))
            ->map(fn (Collection $rows, $key) => (object) [
                $period => (string) $key,
                'total_revenue' => $rows->sum('total_revenue'),
                'order_count' => $rows->sum('order_count'),
            ])->values();
    }

    public function export(Request $request)
    {
        $query = $this->paidOrders($this->parseFilters($request))->orderBy('orders.id');

        return response()->streamDownload(function () use ($query) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Mã đơn', 'Ngày tạo', 'Tổng thu (VND)', 'Phí giao hàng', 'Giảm giá', 'Điểm dùng', 'Loại']);
            foreach ($query->cursor() as $order) {
                fputcsv($file, [$order->id, $order->created_at->format('Y-m-d H:i'), $order->total_price,
                    $order->ghn_total_fee, $order->discount_amount, $order->points_used, $order->is_demo ? 'DEMO' : 'Thực']);
            }
            fclose($file);
        }, 'doanh-thu-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function index(Request $request): View
    {
        $filters = $this->parseFilters($request);
        $categories = Category::all();

        $categoryRevenue = $this->categoryRevenue($filters);

        $totalOrdersQuery = Order::where('is_demo', ($filters['mode'] ?? 'real') === 'demo')->where('created_at', '<=', now());
        if (! empty($filters['date_from'])) {
            $totalOrdersQuery->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (! empty($filters['date_to'])) {
            $totalOrdersQuery->where('created_at', '<', Carbon::parse($filters['date_to'])->addDay()->startOfDay());
        }
        $totalOrders = $totalOrdersQuery->count();

        $totalCustomers = DB::table('users')->whereIn('role', ['user', 'customer'])->count();
        $revenueByDate = $this->dailyRevenue($filters);
        $revenueByMonth = $this->periodRevenue($revenueByDate, 'month');
        $revenueByYear = $this->periodRevenue($revenueByDate, 'year');
        $totalRevenue = $revenueByDate->sum('total_revenue');

        return view('admin.reports.index', compact(
            'categoryRevenue', 'totalOrders', 'totalCustomers', 'totalRevenue',
            'revenueByDate', 'revenueByMonth', 'revenueByYear', 'categories', 'filters'
        ));
    }

    public function charts(Request $request): View
    {
        $filters = $this->parseFilters($request);
        $categoriesList = Category::all();

        $categories = $this->categoryRevenue($filters);
        $catLabels = $categories->map(fn ($row) => $row->category_name ?? 'Danh mục #'.$row->category_id)->all();
        $catRevenue = $categories->pluck('total_revenue')->map(fn ($value) => (float) $value)->all();

        $daily = $this->dailyRevenue($filters);
        $byDate = $daily->keyBy('date');
        $byMonth = $this->periodRevenue($daily, 'month')->keyBy('month');
        $byYear = $this->periodRevenue($daily, 'year');

        // Anchor chart windows to the selected period, including historical and one-sided filters.
        $endDay = Carbon::parse($filters['date_to'] ?: now()->toDateString())->startOfDay();
        $startDay = ! empty($filters['date_from'])
            ? Carbon::parse($filters['date_from'])->startOfDay()
            : $endDay->copy()->subDays(29);
        if ($startDay->gt($endDay)) {
            $endDay = $startDay->copy();
        }
        $startDay = $startDay->max($endDay->copy()->subDays(89));
        $dayCount = (int) $startDay->diffInDays($endDay) + 1;
        $chartDateRange = $startDay->format('d/m/Y').' – '.$endDay->format('d/m/Y');

        $endMonth = $endDay->copy()->startOfMonth();
        $startMonth = ! empty($filters['date_from'])
            ? Carbon::parse($filters['date_from'])->startOfMonth()->max($endMonth->copy()->subMonths(11))
            : $endMonth->copy()->subMonths(11);
        $monthCount = (int) $startMonth->diffInMonths($endMonth) + 1;
        $chartMonthRange = $startMonth->format('m/Y').' – '.$endMonth->format('m/Y');

        $revDateLabels = $revDateData = $revMonthLabels = $revMonthData = [];

        for ($i = 0; $i < $dayCount; $i++) {
            $date = $startDay->copy()->addDays($i)->toDateString();
            $revDateLabels[] = $date;
            $revDateData[] = (float) ($byDate->get($date)?->total_revenue ?? 0);
        }

        for ($i = 0; $i < $monthCount; $i++) {
            $month = $startMonth->copy()->addMonths($i);
            $revMonthLabels[] = $month->format('m/Y');
            $revMonthData[] = (float) ($byMonth->get($month->format('Y-m'))?->total_revenue ?? 0);
        }

        $revYearLabels = $byYear->pluck('year')->all();
        $revYearData = $byYear->pluck('total_revenue')->map(fn ($value) => (float) $value)->all();

        $gateway = DB::table('payment_transactions')->select('gateway')
            ->whereColumn('order_id', 'orders.id')->where('status', 'paid')->orderByDesc('id')->limit(1);

        $paid = $this->paidOrders($filters)->select('orders.total_price')->selectSub($gateway, 'gateway')
            ->selectRaw("CASE WHEN orders.status = 'cod_paid' THEN 'cod' WHEN orders.status IN ('paid', 'paid_momo') THEN 'momo' ELSE 'unknown' END as legacy_gateway");

        $methodRevenue = DB::query()->fromSub($paid, 'paid_orders')
            ->selectRaw('COALESCE(gateway, legacy_gateway) as method, SUM(total_price) as revenue')
            ->groupByRaw('COALESCE(gateway, legacy_gateway)')->pluck('revenue', 'method');

        $gatewayLabels = ['sepay' => 'SePay', 'momo' => 'MoMo', 'cod' => 'COD', 'demo' => 'Mô phỏng', 'unknown' => 'Chưa xác định'];
        $paymentMethodLabels = $methodRevenue->keys()->map(fn ($gateway) => $gatewayLabels[$gateway] ?? 'Khác')->all();
        $paymentMethodRevenue = $methodRevenue->values()->map(fn ($value) => (float) $value)->all();
        $hasRevenue = $daily->isNotEmpty();

        return view('admin.reports.charts', compact(
            'catLabels', 'catRevenue', 'revDateLabels', 'revDateData',
            'revMonthLabels', 'revMonthData', 'revYearLabels', 'revYearData',
            'paymentMethodLabels', 'paymentMethodRevenue', 'categoriesList', 'filters',
            'chartDateRange', 'chartMonthRange', 'hasRevenue'
        ));
    }
}
