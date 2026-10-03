<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'name')) {
                $table->string('name')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('orders', 'shipping_status')) {
                $table->string('shipping_status')->default('not_shipped')->after('status');
            }
            if (!Schema::hasColumn('orders', 'ghn_order_code')) {
                $table->string('ghn_order_code')->nullable()->index()->after('shipping_status');
            }
            if (!Schema::hasColumn('orders', 'ghn_total_fee')) {
                $table->integer('ghn_total_fee')->default(0)->after('ghn_order_code');
            }
            if (!Schema::hasColumn('orders', 'to_district_id')) {
                $table->integer('to_district_id')->nullable()->after('ghn_total_fee');
            }
            if (!Schema::hasColumn('orders', 'to_ward_code')) {
                $table->string('to_ward_code')->nullable()->after('to_district_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['ghn_order_code']);
            $table->dropColumn([
                'name',
                'shipping_status',
                'ghn_order_code',
                'ghn_total_fee',
                'to_district_id',
                'to_ward_code',
            ]);
        });
    }
};
