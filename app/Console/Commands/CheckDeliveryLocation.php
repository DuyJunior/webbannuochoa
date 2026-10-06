<?php

namespace App\Console\Commands;

use App\Exceptions\DeliveryLocationException;
use App\Services\GHNService;
use App\Services\PhotonGeocoder;
use Illuminate\Console\Command;
use Throwable;

class CheckDeliveryLocation extends Command
{
    protected $signature = 'delivery-location:check';

    protected $description = 'Check map and GHN connectivity with a public landmark; no customer location or order is used';

    public function handle(PhotonGeocoder $map, GHNService $ghn): int
    {
        $this->line('Read-only check. Test location: Hoan Kiem Lake. No orders, customer data or secret values.');
        $passed = true;
        $start = microtime(true);
        try {
            $map->reverse(21.0288, 105.8525);
            $this->info('PASS Map lookup ('.round(microtime(true) - $start, 2).'s)');
        } catch (Throwable $error) {
            $passed = false;
            $reason = $error instanceof DeliveryLocationException ? $error->getMessage() : 'unavailable';
            $reason = in_array($reason, ['connection', 'busy', 'not_found', 'unavailable'], true) ? $reason : 'unavailable';
            $this->error('FAIL Map lookup: '.$reason.' ('.round(microtime(true) - $start, 2).'s)');
        }
        try {
            $response = $ghn->getProvinces();
            $ok = ($response['code'] ?? null) == 200 && is_array($response['data'] ?? null) && count($response['data']) > 0;
        } catch (Throwable) {
            $ok = false;
        }
        $this->line(($ok ? 'PASS' : 'FAIL').' GHN area lookup');
        $this->line('Map and GHN are separate services. A GHN token does not repair a failed map connection.');

        return $passed && $ok ? self::SUCCESS : self::FAILURE;
    }
}
