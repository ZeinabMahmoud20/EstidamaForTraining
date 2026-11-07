<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_day_trainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_day_id');
            $table->foreignId('trainer_id');
            $table->enum('actual_role', ['primary', 'backup']);
            $table->foreignId('replaced_trainer_id')->nullable();
            $table->string('reason', 300)->nullable();
            $table->decimal('actual_hours', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['session_day_id', 'trainer_id'], 'session_day_trainers_session_day_id_trainer_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_day_trainers');
    }
};
