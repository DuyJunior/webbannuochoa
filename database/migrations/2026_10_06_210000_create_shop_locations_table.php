<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_locations', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('name', 80);
            $table->string('address', 255);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('hours', 160)->nullable();
            $table->boolean('is_demo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_locations');
    }
};
