<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->constrained('payment_transactions')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('from_status');
            $table->string('to_status');
            $table->string('order_status_before');
            $table->string('order_status_after');
            $table->string('manual_refund_reference', 120)->nullable();
            // Not unique: pending -> failed -> pending -> failed is a valid later transition.
            $table->char('request_fingerprint', 64)->index();
            $table->timestamp('created_at');
            $table->index(['order_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_status_events');
    }
};
