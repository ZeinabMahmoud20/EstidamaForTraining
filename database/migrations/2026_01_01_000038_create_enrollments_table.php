<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nomination_id')->unique();
            $table->foreignId('session_id');
            $table->foreignId('trainee_id');
            $table->enum('status', ['enrolled', 'completed', 'failed', 'withdrawn', 'dismissed'])->default('enrolled');
            $table->decimal('attendance_pct', 5, 2)->nullable();
            $table->decimal('final_score_pct', 5, 2)->nullable();
            $table->boolean('is_passed')->nullable();
            $table->boolean('is_certificate_eligible')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'trainee_id'], 'enrollments_session_id_trainee_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
