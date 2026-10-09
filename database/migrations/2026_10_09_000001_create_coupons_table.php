<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->enum('type', ['percentage', 'nominal', 'free'])->default('percentage');
            $table->decimal('value', 15, 2)->default(0)->comment('percentage: 0-100; nominal: rupiah; free: 0');
            $table->decimal('min_transaction', 15, 2)->default(0);
            $table->integer('max_usage_total')->default(0)->comment('0 = unlimited');
            $table->integer('max_usage_per_user')->default(0)->comment('0 = unlimited');
            $table->string('jenjang_target', 10)->nullable()->comment('SD/SMP/SMA or null = all');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('jenjang_target');
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('identifier')->nullable()->comment('nomor_wa or email for guest users');
            $table->string('reference_code')->nullable()->comment('transaction/order reference');
            $table->decimal('original_amount', 15, 2)->default(0);
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('final_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['coupon_id', 'user_id']);
            $table->index(['coupon_id', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};
