<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('material_practice_package_attempts', 'paket_no')) {
            Schema::table('material_practice_package_attempts', function (Blueprint $table) {
                $table->unsignedTinyInteger('paket_no')->nullable()->after('material_practice_package_id');
            });
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('UPDATE material_practice_package_attempts a JOIN material_practice_packages p ON a.material_practice_package_id = p.id SET a.paket_no = p.paket_no WHERE a.paket_no IS NULL');
        } else {
            DB::statement('UPDATE material_practice_package_attempts SET paket_no = (SELECT p.paket_no FROM material_practice_packages p WHERE p.id = material_practice_package_attempts.material_practice_package_id) WHERE material_practice_package_id IS NOT NULL AND paket_no IS NULL');
        }

        if ($this->foreignExists('material_practice_package_attempts', 'mpr_attempt_pkg_fk')) {
            Schema::table('material_practice_package_attempts', function (Blueprint $table) {
                $table->dropForeign('mpr_attempt_pkg_fk');
            });
        }

        Schema::table('material_practice_package_attempts', function (Blueprint $table) {
            $table->foreignId('material_practice_package_id')->nullable()->change();
        });

        if (! $this->foreignExists('material_practice_package_attempts', 'mpr_attempt_pkg_new_fk')) {
            Schema::table('material_practice_package_attempts', function (Blueprint $table) {
                $table->foreign('material_practice_package_id', 'mpr_attempt_pkg_new_fk')
                    ->references('id')
                    ->on('material_practice_packages')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if ($this->foreignExists('material_practice_package_attempts', 'mpr_attempt_pkg_new_fk')) {
            Schema::table('material_practice_package_attempts', function (Blueprint $table) {
                $table->dropForeign('mpr_attempt_pkg_new_fk');
            });
        }

        Schema::table('material_practice_package_attempts', function (Blueprint $table) {
            $table->foreignId('material_practice_package_id')->nullable(false)->change();
        });

        if (! $this->foreignExists('material_practice_package_attempts', 'mpr_attempt_pkg_fk')) {
            Schema::table('material_practice_package_attempts', function (Blueprint $table) {
                $table->foreign('material_practice_package_id', 'mpr_attempt_pkg_fk')
                    ->references('id')
                    ->on('material_practice_packages')
                    ->cascadeOnDelete();
            });
        }

        Schema::table('material_practice_package_attempts', function (Blueprint $table) {
            $table->dropColumn('paket_no');
        });
    }

    private function foreignExists(string $table, string $name): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        return (bool) DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = \'FOREIGN KEY\'',
            [DB::connection()->getDatabaseName(), $table, $name],
        );
    }
};
