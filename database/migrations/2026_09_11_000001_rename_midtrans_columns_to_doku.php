<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (Schema::hasColumn('transactions', 'midtrans_order_id')) {
                    $table->renameColumn('midtrans_order_id', 'doku_invoice_number');
                }

                if (Schema::hasColumn('transactions', 'midtrans_transaction_status')) {
                    $table->renameColumn('midtrans_transaction_status', 'doku_transaction_status');
                }

                if (Schema::hasColumn('transactions', 'midtrans_payment_type')) {
                    $table->renameColumn('midtrans_payment_type', 'doku_payment_channel');
                }
            });

            DB::table('transactions')
                ->where('payment_method', 'midtrans')
                ->update(['payment_method' => 'doku']);
        }

        if (Schema::hasTable('landing_exam_orders')) {
            Schema::table('landing_exam_orders', function (Blueprint $table) {
                if (Schema::hasColumn('landing_exam_orders', 'midtrans_order_id')) {
                    $table->renameColumn('midtrans_order_id', 'doku_invoice_number');
                }

                if (Schema::hasColumn('landing_exam_orders', 'midtrans_transaction_status')) {
                    $table->renameColumn('midtrans_transaction_status', 'doku_transaction_status');
                }

                if (Schema::hasColumn('landing_exam_orders', 'midtrans_payment_type')) {
                    $table->renameColumn('midtrans_payment_type', 'doku_payment_channel');
                }
            });
        }

        if (Schema::hasTable('app_settings')) {
            DB::table('app_settings')->where('key', 'like', 'midtrans_%')->delete();

            $adminWhatsapp = DB::table('app_settings')->where('key', 'qris_admin_whatsapp')->value('value');

            if ($adminWhatsapp !== null) {
                DB::table('app_settings')->updateOrInsert(
                    ['key' => 'admin_whatsapp'],
                    ['value' => $adminWhatsapp],
                );
            }

            DB::table('app_settings')->where('key', 'qris_admin_whatsapp')->delete();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (Schema::hasColumn('transactions', 'doku_invoice_number')) {
                    $table->renameColumn('doku_invoice_number', 'midtrans_order_id');
                }

                if (Schema::hasColumn('transactions', 'doku_transaction_status')) {
                    $table->renameColumn('doku_transaction_status', 'midtrans_transaction_status');
                }

                if (Schema::hasColumn('transactions', 'doku_payment_channel')) {
                    $table->renameColumn('doku_payment_channel', 'midtrans_payment_type');
                }
            });

            DB::table('transactions')
                ->where('payment_method', 'doku')
                ->update(['payment_method' => 'midtrans']);
        }

        if (Schema::hasTable('landing_exam_orders')) {
            Schema::table('landing_exam_orders', function (Blueprint $table) {
                if (Schema::hasColumn('landing_exam_orders', 'doku_invoice_number')) {
                    $table->renameColumn('doku_invoice_number', 'midtrans_order_id');
                }

                if (Schema::hasColumn('landing_exam_orders', 'doku_transaction_status')) {
                    $table->renameColumn('doku_transaction_status', 'midtrans_transaction_status');
                }

                if (Schema::hasColumn('landing_exam_orders', 'doku_payment_channel')) {
                    $table->renameColumn('doku_payment_channel', 'midtrans_payment_type');
                }
            });
        }

        if (Schema::hasTable('app_settings')) {
            $adminWhatsapp = DB::table('app_settings')->where('key', 'admin_whatsapp')->value('value');

            if ($adminWhatsapp !== null) {
                DB::table('app_settings')->updateOrInsert(
                    ['key' => 'qris_admin_whatsapp'],
                    ['value' => $adminWhatsapp],
                );
            }

            DB::table('app_settings')->where('key', 'admin_whatsapp')->delete();
        }
    }
};
