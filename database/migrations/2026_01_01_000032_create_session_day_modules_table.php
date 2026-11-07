<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_day_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_day_id');
            $table->foreignId('module_id');
            $table->foreignId('trainer_id')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_day_modules');
    }
};
