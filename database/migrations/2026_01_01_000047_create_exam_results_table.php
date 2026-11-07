<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id');
            $table->foreignId('enrollment_id');
            $table->decimal('score', 6, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('is_passed')->nullable();
            $table->enum('status', ['pending_grading', 'graded'])->nullable();
            $table->dateTime('taken_at')->nullable();
            $table->foreignId('graded_by')->nullable();
            $table->timestamps();
            $table->unique(['exam_id', 'enrollment_id'], 'exam_results_exam_id_enrollment_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
