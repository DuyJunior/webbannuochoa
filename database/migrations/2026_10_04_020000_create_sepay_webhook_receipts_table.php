<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepay_webhook_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('provider_id', 30)->unique();
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('payment_code', 64)->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('reference_code', 255)->nullable();
            $table->string('result', 40)->index();
            $table->timestamp('received_at');
            $table->timestamp('shipment_attempted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepay_webhook_receipts');
    }
};
