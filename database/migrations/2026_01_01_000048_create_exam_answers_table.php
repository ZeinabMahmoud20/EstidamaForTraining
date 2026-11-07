<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id');
            $table->foreignId('question_id');
            $table->foreignId('selected_option_id')->nullable();
            $table->text('text_answer')->nullable();
            $table->decimal('points_awarded', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
    }
};
