<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id');
            $table->enum('exam_type', ['pre', 'post', 'final', 'practical']);
            $table->string('title', 200)->nullable();
            $table->dateTime('scheduled_start')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->decimal('total_score', 6, 2)->nullable();
            $table->decimal('pass_score', 6, 2)->nullable();
            $table->decimal('weight_pct', 5, 2)->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->index(['session_id', 'exam_type'], 'exams_session_id_exam_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
