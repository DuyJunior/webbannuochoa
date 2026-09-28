<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfumes', function (Blueprint $table) {
            $table->unsignedInteger('stock_5ml')->default(0);
        });
        Schema::table('orders', function (Blueprint $table) {
            // Historical orders have ambiguous stock provenance: never restore them blindly.
            $table->string('inventory_status')->default('legacy');
            $table->boolean('is_demo')->default(false);
        });
        Schema::table('order_items', fn (Blueprint $table) => $table->json('stock_components')->nullable());
        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('from_shipping')->nullable();
            $table->string('to_shipping')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('stock_components'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['inventory_status', 'is_demo']));
        Schema::table('perfumes', fn (Blueprint $table) => $table->dropColumn('stock_5ml'));
    }
};
