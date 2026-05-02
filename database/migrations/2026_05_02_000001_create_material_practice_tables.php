<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_practice_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials', 'id', 'mpr_token_material_fk')->cascadeOnDelete();
            $table->string('token', 20)->unique();
            $table->unsignedInteger('jumlah_soal_per_paket')->default(10);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users', 'id', 'mpr_token_created_by_fk')->nullOnDelete();
            $table->timestamps();

            $table->unique(['material_id'], 'mpr_tokens_material_unique');
        });

        Schema::create('material_telaah_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials', 'id', 'mtq_material_fk')->cascadeOnDelete();
            $table->foreignId('global_question_id')->constrained('global_questions', 'id', 'mtq_gq_fk')->cascadeOnDelete();
            $table->unsignedTinyInteger('urutan');
            $table->timestamps();

            $table->unique(['material_id', 'urutan'], 'mtq_material_urutan_unique');
            $table->unique(['material_id', 'global_question_id'], 'mtq_material_question_unique');
        });

        Schema::create('material_practice_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_practice_token_id')->constrained('material_practice_tokens', 'id', 'mpr_pkg_token_fk')->cascadeOnDelete();
            $table->unsignedTinyInteger('paket_no');
            $table->timestamps();

            $table->unique(['material_practice_token_id', 'paket_no'], 'mpr_pkg_token_paket_unique');
        });

        Schema::create('material_practice_package_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_practice_package_id')->constrained('material_practice_packages', 'id', 'mpr_pkgq_pkg_fk')->cascadeOnDelete();
            $table->foreignId('global_question_id')->constrained('global_questions', 'id', 'mpr_pkgq_gq_fk')->cascadeOnDelete();
            $table->unsignedInteger('urutan');
            $table->timestamps();

            $table->unique(['material_practice_package_id', 'urutan'], 'mpr_pkgq_pkg_urutan_unique');
            $table->unique(['material_practice_package_id', 'global_question_id'], 'mpr_pkgq_pkg_question_unique');
        });

        Schema::create('material_practice_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_practice_token_id')->constrained('material_practice_tokens', 'id', 'mpr_session_token_fk')->cascadeOnDelete();
            $table->string('nama');
            $table->string('nomor_wa', 20)->nullable();
            $table->string('session_token', 80)->unique();
            $table->enum('status', ['menunggu', 'mengerjakan', 'selesai'])->default('menunggu');
            $table->timestamp('waktu_mulai')->nullable();
            $table->timestamp('waktu_selesai')->nullable();
            $table->timestamps();

            $table->index(['material_practice_token_id', 'status'], 'mpr_session_token_status_idx');
        });

        Schema::create('material_telaah_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_practice_session_id')->constrained('material_practice_sessions', 'id', 'mta_session_fk')->cascadeOnDelete();
            $table->foreignId('global_question_id')->constrained('global_questions', 'id', 'mta_gq_fk')->cascadeOnDelete();
            $table->string('jawaban')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['material_practice_session_id', 'global_question_id'], 'mta_session_question_unique');
        });

        Schema::create('material_practice_package_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_practice_session_id')->constrained('material_practice_sessions', 'id', 'mpr_attempt_session_fk')->cascadeOnDelete();
            $table->foreignId('material_practice_package_id')->constrained('material_practice_packages', 'id', 'mpr_attempt_pkg_fk')->cascadeOnDelete();
            $table->enum('status', ['mengerjakan', 'selesai'])->default('mengerjakan');
            $table->timestamp('waktu_mulai')->nullable();
            $table->timestamp('waktu_selesai')->nullable();
            $table->unsignedInteger('total_soal')->default(0);
            $table->unsignedInteger('benar')->default(0);
            $table->decimal('skor', 6, 2)->nullable();
            $table->timestamps();

            $table->unique(['material_practice_session_id', 'material_practice_package_id'], 'mpr_attempt_session_pkg_unique');
            $table->index(['material_practice_package_id', 'status'], 'mpr_attempt_pkg_status_idx');
        });

        Schema::create('material_practice_package_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_practice_package_attempt_id')->constrained('material_practice_package_attempts', 'id', 'mpr_answer_attempt_fk')->cascadeOnDelete();
            $table->foreignId('global_question_id')->constrained('global_questions', 'id', 'mpr_answer_gq_fk')->cascadeOnDelete();
            $table->string('jawaban')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['material_practice_package_attempt_id', 'global_question_id'], 'mpr_answer_attempt_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_practice_package_answers');
        Schema::dropIfExists('material_practice_package_attempts');
        Schema::dropIfExists('material_telaah_answers');
        Schema::dropIfExists('material_practice_sessions');
        Schema::dropIfExists('material_practice_package_questions');
        Schema::dropIfExists('material_practice_packages');
        Schema::dropIfExists('material_telaah_questions');
        Schema::dropIfExists('material_practice_tokens');
    }
};
