<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_trainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id');
            $table->foreignId('trainer_id');
            $table->enum('planned_role', ['primary', 'backup']);
            $table->foreignId('assigned_by')->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'trainer_id'], 'session_trainers_session_id_trainer_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_trainers');
    }
};
