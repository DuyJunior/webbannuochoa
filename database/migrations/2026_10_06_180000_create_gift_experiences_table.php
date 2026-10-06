<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('perfume_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->string('pin_hash');
            $table->unsignedInteger('access_version')->default(1);
            $table->string('sender_name', 80);
            $table->string('recipient_name', 80);
            $table->text('message');
            $table->string('photo_path')->nullable();
            $table->string('audio_path')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('opened_at')->nullable();
            $table->text('thank_you')->nullable();
            $table->timestamp('thanked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_experiences');
    }
};
