<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('payment_method')->default('manual_qris')->after('status');
            $table->string('midtrans_order_id', 100)->nullable()->after('payment_method');
            $table->string('midtrans_transaction_status', 50)->nullable()->after('midtrans_order_id');
            $table->string('midtrans_payment_type', 50)->nullable()->after('midtrans_transaction_status');
            $table->timestamp('paid_at')->nullable()->after('midtrans_payment_type');
            $table->index('midtrans_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['midtrans_order_id']);
            $table->dropColumn([
                'payment_method',
                'midtrans_order_id',
                'midtrans_transaction_status',
                'midtrans_payment_type',
                'paid_at',
            ]);
        });
    }
};
