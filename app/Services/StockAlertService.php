<?php

namespace App\Services;

use App\Mail\BackInStockMail;
use App\Models\Perfume;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class StockAlertService
{
    public static function notifyIfRestocked(Perfume|Product $perfume, int $previousStock): void
    {
        if ($previousStock > 0 || $perfume->availableStock() <= 0 || ! $perfume->is_active) {
            return;
        }

        $alerts = DB::table('stock_alerts')->join('users', 'users.id', '=', 'stock_alerts.user_id')
            ->where('stock_alerts.perfume_id', $perfume->id)->whereNull('stock_alerts.notified_at')
            ->select('stock_alerts.id', 'users.email')->get();

        foreach ($alerts as $alert) {
            try {
                $volumes = array_values(array_filter($perfume->saleVolumes(), fn ($volume) => $perfume->getStockForVolume($volume) > 0));
                Mail::to($alert->email)->send(new BackInStockMail($perfume, $volumes));
                DB::table('stock_alerts')->where('id', $alert->id)->update(['notified_at' => now()]);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }
}
