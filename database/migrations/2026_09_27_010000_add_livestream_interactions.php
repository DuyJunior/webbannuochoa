<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('livestreams', function (Blueprint $table) {
            $table->foreignId('pinned_perfume_id')->nullable()->constrained('perfumes')->nullOnDelete();
        });

        Schema::create('livestream_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livestream_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('display_name', 60);
            $table->string('body', 300);
            $table->boolean('is_staff')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
            $table->index(['livestream_id', 'is_hidden', 'id']);
        });

        Schema::create('livestream_viewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livestream_id')->constrained()->cascadeOnDelete();
            $table->char('session_hash', 64);
            $table->timestamp('last_seen_at');
            $table->timestamps();
            $table->unique(['livestream_id', 'session_hash']);
            $table->index(['livestream_id', 'last_seen_at']);
        });

        Schema::create('livestream_product_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livestream_id')->constrained()->cascadeOnDelete();
            $table->foreignId('perfume_id')->nullable()->constrained('perfumes')->nullOnDelete();
            $table->char('session_hash', 64);
            $table->timestamps();
            $table->index(['livestream_id', 'perfume_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livestream_product_clicks');
        Schema::dropIfExists('livestream_viewers');
        Schema::dropIfExists('livestream_messages');
        Schema::table('livestreams', fn (Blueprint $table) => $table->dropConstrainedForeignId('pinned_perfume_id'));
    }
};
