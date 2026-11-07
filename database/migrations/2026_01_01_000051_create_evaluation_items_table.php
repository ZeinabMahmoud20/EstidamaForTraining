<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id');
            $table->foreignId('section_id')->nullable();
            $table->string('item_text', 500);
            $table->enum('answer_type', ['scale_1_5', 'text']);
            $table->decimal('weight', 5, 2)->default(1);
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_items');
    }
};
