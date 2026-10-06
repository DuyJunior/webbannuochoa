<?php

namespace App\Services;

use App\Models\ShopLocation;
use Illuminate\Support\Facades\Schema;

class ShopLocationService
{
    public function current(): array
    {
        $saved = $this->saved();
        return $saved ? $saved->toArray() : array_merge(config('storefront.location'), [
            'hours' => config('storefront.support_hours'), 'is_demo' => true,
        ]);
    }

    public function saved(): ?ShopLocation
    {
        // Keep initial setup working before this feature's migration runs.
        return Schema::hasTable('shop_locations') ? ShopLocation::find(1) : null;
    }
}
