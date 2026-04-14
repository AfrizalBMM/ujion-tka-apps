<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('curriculum');
            $table->string('subelement');
            $table->string('unit');
            $table->string('sub_unit');
            $table->timestamps();

            $table->unique(['curriculum', 'subelement', 'unit', 'sub_unit']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
