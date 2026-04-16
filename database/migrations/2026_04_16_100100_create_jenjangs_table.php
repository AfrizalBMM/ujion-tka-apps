<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenjangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->unsignedInteger('urutan')->default(1);
            $table->timestamps();
        });

        DB::table('jenjangs')->insert([
            [
                'kode' => 'SD',
                'nama' => 'Sekolah Dasar',
                'urutan' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'SMP',
                'nama' => 'Sekolah Menengah Pertama',
                'urutan' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('jenjangs');
    }
};
