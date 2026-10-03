<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfume_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perfume_id')->constrained('perfumes')->restrictOnDelete();
            $table->unsignedInteger('volume_ml');
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('weight')->default(200);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['perfume_id', 'volume_ml']);
        });
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->unsignedInteger('volume_ml')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', fn (Blueprint $table) => $table->dropColumn('volume_ml'));
        Schema::dropIfExists('perfume_variants');
    }
};
