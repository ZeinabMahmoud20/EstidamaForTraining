<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id');
            $table->string('title', 200);
            $table->decimal('weight', 5, 2)->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_sections');
    }
};
