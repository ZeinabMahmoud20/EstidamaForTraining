<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_specialties', function (Blueprint $table) {
            $table->foreignId('trainer_id');
            $table->foreignId('category_id');
            $table->primary(['trainer_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_specialties');
    }
};
