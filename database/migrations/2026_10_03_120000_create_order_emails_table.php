<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('recipient');
            $table->json('details');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->uuid('processing_token')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->string('last_error')->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'type']);
            $table->index(['sent_at', 'skipped_at', 'available_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_emails');
    }
};
