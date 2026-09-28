<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->call([AdminUserSeeder::class, PerfumeCatalogSeeder::class]);
        });
    }
}
