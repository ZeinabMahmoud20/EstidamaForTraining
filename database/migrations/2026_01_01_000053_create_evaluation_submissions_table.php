<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id');
            $table->foreignId('session_id');
            $table->enum('evaluator_type', ['trainee', 'trainer', 'supervisor', 'provider']);
            $table->foreignId('evaluator_user_id')->nullable();
            $table->enum('target_type', ['trainer', 'program', 'trainee', 'venue', 'operations']);
            $table->foreignId('target_trainer_id')->nullable();
            $table->foreignId('target_enrollment_id')->nullable();
            $table->foreignId('target_hall_id')->nullable();
            $table->decimal('total_score', 6, 2)->nullable();
            $table->dateTime('submitted_at')->useCurrent();
            $table->timestamps();
            $table->index(['target_type', 'target_trainer_id'], 'evaluation_submissions_target_type_target_trainer_id_index');
            $table->index(['session_id', 'target_type'], 'evaluation_submissions_session_id_target_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_submissions');
    }
};
