<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials');
            $table->string('jenjang');
            $table->string('tingkat');
            $table->string('kategori'); // Mudah, Sedang, Susah
            $table->string('tipe'); // PG, singkat, checklist
            $table->text('pertanyaan');
            $table->json('opsi')->nullable(); // Untuk PG/Checklist
            $table->string('jawaban_benar')->nullable();
            $table->text('pembahasan')->nullable();
            $table->string('image_path')->nullable();
            $table->enum('status', ['draft', 'terbit'])->default('draft');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('questions');
    }
};