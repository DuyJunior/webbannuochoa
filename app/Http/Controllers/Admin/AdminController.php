<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $request->validate(['days' => ['nullable', 'integer', 'in:7,30']]);
        $days = (int) $request->input('days', 7);
        $start = now()->startOfDay()->subDays($days - 1);
        $daily = Order::where('status', '!=', 'cancelled')->whereBetween('created_at', [$start, now()->endOfDay()])
            ->selectRaw('DATE(created_at) as day, SUM(total_price) as amount, COUNT(*) as orders_count')
            ->groupBy('day')->get()->keyBy('day');
        $chart = collect(range(0, $days - 1))->map(function ($offset) use ($start, $daily) {
            $day = $start->copy()->addDays($offset);
            $row = $daily->get($day->format('Y-m-d'));

            return ['date' => $day->format('d/m'), 'amount' => (float) ($row->amount ?? 0), 'count' => (int) ($row->orders_count ?? 0)];
        });
        $stats = [
            'products' => Product::count(),
            'activeProducts' => Product::where('is_active', true)->count(),
            'orders' => Order::count(),
            'pending' => Order::where('status', '!=', 'cancelled')->whereIn('shipping_status', ['pending', 'not_shipped', 'processing'])->count(),
            'value' => (float) Order::where('status', '!=', 'cancelled')->sum('total_price'),
        ];
        $recentOrders = Order::latest()->orderByDesc('id')->limit(5)->get();
        $lowStock = Product::where('is_active', true)->where('stock', '<=', 5)->orderBy('stock')->limit(4)->get();

        return view('admin.dashboard', compact('stats', 'chart', 'days', 'recentOrders', 'lowStock'));
    }

    public function products()
    {
        return app(ProductController::class)->index();
    }

    public function categories()
    {
        return app(CategoryController::class)->index();
    }
}
