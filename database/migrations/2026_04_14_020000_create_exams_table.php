<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('judul');
            $table->dateTime('tanggal_terbit');
            $table->integer('max_peserta')->default(50);
            $table->string('token', 6)->unique();
            $table->integer('timer')->nullable();
            $table->enum('status', ['draft', 'terbit'])->default('draft');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('exams');
    }
};