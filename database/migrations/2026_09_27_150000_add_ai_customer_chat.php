<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_usage', function (Blueprint $table) {
            $table->date('day')->primary();
            $table->unsignedInteger('requests')->default(0);
        });
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('human_mode')->default(false);
            $table->timestamps();
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('is_ai')->default(false);
            $table->foreignId('reply_to_id')->nullable()->unique()->constrained('messages')->nullOnDelete();
            $table->string('ai_status', 20)->nullable()->index();
            $table->index(['sender_id', 'receiver_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_id']);
            $table->dropUnique(['reply_to_id']);
            $table->dropIndex(['ai_status']);
            $table->dropIndex(['sender_id', 'receiver_id', 'id']);
            $table->dropColumn(['is_ai', 'reply_to_id', 'ai_status']);
        });
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('ai_chat_usage');
    }
};
