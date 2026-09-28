<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfumes', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('checkout_key')->nullable();
            $table->timestamp('payment_expires_at')->nullable()->index();
            $table->unique(['user_id', 'checkout_key']);
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_name')->nullable();
            $table->string('product_brand')->nullable();
        });
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('perfume_id')->constrained()->restrictOnDelete();
            $table->string('stock_column', 30);
            $table->string('operation', 20);
            $table->integer('quantity_change');
            $table->unsignedInteger('balance_after');
            $table->timestamp('created_at');
            $table->unique(['order_id', 'perfume_id', 'stock_column', 'operation'], 'inventory_order_operation_unique');
        });

        // Legacy snapshots reflect the catalog at migration time, not a reconstructed original name.
        DB::table('order_items')->orderBy('id')->chunkById(200, function ($items) {
            foreach ($items as $item) {
                $product = DB::table('perfumes')->find($item->perfume_id);
                if ($product) {
                    DB::table('order_items')->where('id', $item->id)->update([
                        'product_name' => $product->name, 'product_brand' => $product->brand,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'checkout_key']);
            $table->dropIndex(['payment_expires_at']);
            $table->dropColumn(['checkout_key', 'payment_expires_at']);
        });
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn(['product_name', 'product_brand']));
        Schema::table('perfumes', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
