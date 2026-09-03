<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'payment_proof_path')) {
                $table->dropColumn('payment_proof_path');
            }

            if (Schema::hasColumn('transactions', 'payment_submitted_at')) {
                $table->dropColumn('payment_submitted_at');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'payment_proof_path')) {
                $table->dropColumn('payment_proof_path');
            }

            if (Schema::hasColumn('users', 'payment_submitted_at')) {
                $table->dropColumn('payment_submitted_at');
            }
        });

        Schema::table('pricing_plans', function (Blueprint $table) {
            if (Schema::hasColumn('pricing_plans', 'qris_image_path')) {
                $table->dropColumn('qris_image_path');
            }
        });

        if (Schema::hasTable('app_settings')) {
            DB::table('app_settings')->where('key', 'qris_master_payload')->delete();
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('payment_proof_path')->nullable();
            $table->timestamp('payment_submitted_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('payment_proof_path')->nullable();
            $table->timestamp('payment_submitted_at')->nullable();
        });

        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->string('qris_image_path')->nullable()->after('description');
        });
    }
};
