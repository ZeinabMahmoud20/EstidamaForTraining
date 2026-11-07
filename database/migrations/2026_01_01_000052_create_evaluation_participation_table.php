<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_participation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id');
            $table->foreignId('session_id');
            $table->foreignId('enrollment_id')->nullable();
            $table->foreignId('trainer_id')->nullable();
            $table->foreignId('target_trainer_id')->nullable();
            $table->foreignId('target_enrollment_id')->nullable();
            $table->foreignId('target_hall_id')->nullable();
            $table->boolean('is_submitted')->default(false);
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_participation');
    }
};
