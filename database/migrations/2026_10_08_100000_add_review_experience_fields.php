<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfume_reviews', function (Blueprint $table) {
            $table->json('images')->nullable();
            $table->json('tags')->nullable();
            $table->text('seller_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('perfume_reviews', fn (Blueprint $table) => $table->dropColumn(['images', 'tags', 'seller_reply', 'replied_at']));
    }
};
