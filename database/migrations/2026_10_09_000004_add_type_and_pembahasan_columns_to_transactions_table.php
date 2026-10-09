<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend transactions table to support pembahasan payments.
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('type', 30)->default('subscription')->after('id')->comment('subscription | pembahasan');
            $table->unsignedBigInteger('ujian_sesi_id')->nullable()->after('pricing_plan_id')->comment('for type=pembahasan');
            $table->decimal('original_amount', 15, 2)->nullable()->after('amount')->comment('before coupon discount');
            $table->unsignedBigInteger('coupon_id')->nullable()->after('reviewed_by')->comment('coupon used');
            $table->string('coupon_code')->nullable()->after('coupon_id');
            $table->decimal('discount_value', 15, 2)->default(0)->after('coupon_code');

            $table->index('type');
            $table->index('ujian_sesi_id');
        });

        // Add pembahasan-specific status to landing_exam_orders
        Schema::table('landing_exam_orders', function (Blueprint $table) {
            $table->decimal('original_amount', 15, 2)->nullable()->after('amount');
            $table->unsignedBigInteger('coupon_id')->nullable()->after('doku_payment_channel');
            $table->string('coupon_code')->nullable()->after('coupon_id');
            $table->decimal('discount_value', 15, 2)->default(0)->after('coupon_code');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['ujian_sesi_id']);
            $table->dropColumn([
                'type',
                'ujian_sesi_id',
                'original_amount',
                'coupon_id',
                'coupon_code',
                'discount_value',
            ]);
        });

        Schema::table('landing_exam_orders', function (Blueprint $table) {
            $table->dropColumn([
                'original_amount',
                'coupon_id',
                'coupon_code',
                'discount_value',
            ]);
        });
    }
};
