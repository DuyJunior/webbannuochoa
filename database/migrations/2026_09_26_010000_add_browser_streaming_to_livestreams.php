<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('livestreams', function (Blueprint $table) {
            $table->string('youtube_video_id', 11)->nullable()->change();
            $table->string('source', 20)->default('youtube');
            $table->foreignId('presenter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_heartbeat_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('livestreams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('presenter_id');
            $table->dropColumn(['source', 'last_heartbeat_at']);
        });
        Schema::table('livestreams', function (Blueprint $table) {
            $table->string('youtube_video_id', 11)->nullable()->change();
        });
    }
};
