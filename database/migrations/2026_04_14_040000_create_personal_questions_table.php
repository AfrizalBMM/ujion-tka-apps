<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('personal_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('jenjang');
            $table->string('kategori');
            $table->string('tipe'); // PG, singkat, checklist
            $table->text('pertanyaan');
            $table->json('opsi')->nullable();
            $table->string('jawaban_benar')->nullable();
            $table->text('pembahasan')->nullable();
            $table->string('image_path')->nullable();
            $table->enum('status', ['draft', 'terbit'])->default('draft');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('personal_questions');
    }
};