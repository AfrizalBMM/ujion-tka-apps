<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_exam_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('landing_exam_mapel_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->string('nomor_wa', 20);
            $table->string('session_token', 80)->unique();
            $table->string('status')->default('pending_payment');
            $table->decimal('amount', 15, 2);
            $table->string('midtrans_order_id', 100)->nullable()->index();
            $table->string('midtrans_transaction_status', 50)->nullable();
            $table->string('midtrans_payment_type', 50)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_exam_orders');
    }
};
