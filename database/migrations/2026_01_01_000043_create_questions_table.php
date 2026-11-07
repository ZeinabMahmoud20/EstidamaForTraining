<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id');
            $table->text('question_text');
            $table->enum('question_type', ['mcq', 'true_false', 'text']);
            $table->smallInteger('difficulty')->nullable();
            $table->decimal('default_points', 5, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
