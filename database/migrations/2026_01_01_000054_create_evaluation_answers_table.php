<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id');
            $table->foreignId('item_id');
            $table->smallInteger('score_value')->nullable();
            $table->text('text_value')->nullable();
            $table->timestamps();
            $table->unique(['submission_id', 'item_id'], 'evaluation_answers_submission_id_item_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_answers');
    }
};
