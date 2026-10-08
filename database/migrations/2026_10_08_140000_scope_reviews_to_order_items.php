<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfume_reviews', function (Blueprint $table) {
            // Preserve historical reviews without inventing an order association.
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->index('user_id', 'perfume_reviews_customer_idx');
        });
        Schema::table('perfume_reviews', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'perfume_id']);
            $table->unique(['order_item_id', 'perfume_id'], 'perfume_reviews_purchase_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('perfume_reviews')->select('user_id', 'perfume_id')->groupBy('user_id', 'perfume_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Cannot restore one-review-per-product uniqueness while purchase-specific reviews exist. Preserve these reviews before rollback.');
        }
        Schema::table('perfume_reviews', function (Blueprint $table) {
            $table->unique(['user_id', 'perfume_id']);
            $table->dropForeign(['order_item_id']);
            $table->dropUnique('perfume_reviews_purchase_unique');
            $table->dropColumn('order_item_id');
            $table->dropIndex('perfume_reviews_customer_idx');
        });
    }
};
