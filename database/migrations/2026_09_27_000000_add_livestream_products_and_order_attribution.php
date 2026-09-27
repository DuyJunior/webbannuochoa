<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('livestream_products', function (Blueprint $table) {
            $table->foreignId('livestream_id')->constrained()->cascadeOnDelete();
            $table->foreignId('perfume_id')->constrained('perfumes')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->primary(['livestream_id', 'perfume_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('livestream_id')->nullable()->constrained()->nullOnDelete();
        });

        foreach (DB::table('livestreams')->whereNotNull('perfume_id')->get(['id', 'perfume_id']) as $stream) {
            DB::table('livestream_products')->insert([
                'livestream_id' => $stream->id,
                'perfume_id' => $stream->perfume_id,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropConstrainedForeignId('livestream_id'));
        Schema::dropIfExists('livestream_products');
    }
};
