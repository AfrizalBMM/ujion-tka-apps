<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_practice_package_attempts', function (Blueprint $table) {
            $table->unsignedTinyInteger('paket_no')->nullable()->after('material_practice_package_id');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('UPDATE material_practice_package_attempts a JOIN material_practice_packages p ON a.material_practice_package_id = p.id SET a.paket_no = p.paket_no');
        } else {
            DB::statement('UPDATE material_practice_package_attempts SET paket_no = (SELECT p.paket_no FROM material_practice_packages p WHERE p.id = material_practice_package_attempts.material_practice_package_id) WHERE material_practice_package_id IS NOT NULL');
        }

        Schema::table('material_practice_package_attempts', function (Blueprint $table) {
            $table->dropForeign(['material_practice_package_id']);
        });

        Schema::table('material_practice_package_attempts', function (Blueprint $table) {
            $table->foreignId('material_practice_package_id')->nullable()->change();
            $table->foreign('material_practice_package_id', 'mpr_attempt_pkg_fk')
                ->references('id')
                ->on('material_practice_packages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('material_practice_package_attempts', function (Blueprint $table) {
            $table->dropForeign(['material_practice_package_id']);
        });

        Schema::table('material_practice_package_attempts', function (Blueprint $table) {
            $table->foreignId('material_practice_package_id')->nullable(false)->change();
            $table->foreign('material_practice_package_id', 'mpr_attempt_pkg_fk')
                ->references('id')
                ->on('material_practice_packages')
                ->cascadeOnDelete();
        });

        Schema::table('material_practice_package_attempts', function (Blueprint $table) {
            $table->dropColumn('paket_no');
        });
    }
};
