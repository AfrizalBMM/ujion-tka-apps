<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_questions', function (Blueprint $table) {
            $table->string('material_curriculum')->nullable()->after('question_text');
            $table->string('material_subelement')->nullable()->after('material_curriculum');
            $table->string('material_unit')->nullable()->after('material_subelement');
            $table->string('material_sub_unit')->nullable()->after('material_unit');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('
                UPDATE global_questions
                SET
                    material_curriculum = (SELECT curriculum FROM materials WHERE materials.id = global_questions.material_id),
                    material_subelement = (SELECT subelement FROM materials WHERE materials.id = global_questions.material_id),
                    material_unit = (SELECT unit FROM materials WHERE materials.id = global_questions.material_id),
                    material_sub_unit = (SELECT sub_unit FROM materials WHERE materials.id = global_questions.material_id)
                WHERE material_id IS NOT NULL
            ');
        } else {
            DB::table('global_questions')
                ->join('materials', 'materials.id', '=', 'global_questions.material_id')
                ->update([
                    'global_questions.material_curriculum' => DB::raw('materials.curriculum'),
                    'global_questions.material_subelement' => DB::raw('materials.subelement'),
                    'global_questions.material_unit' => DB::raw('materials.unit'),
                    'global_questions.material_sub_unit' => DB::raw('materials.sub_unit'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('global_questions', function (Blueprint $table) {
            $table->dropColumn([
                'material_curriculum',
                'material_subelement',
                'material_unit',
                'material_sub_unit',
            ]);
        });
    }
};
