<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id');
            $table->string('title', 250);
            $table->text('description')->nullable();
            $table->decimal('hours', 5, 2)->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_modules');
    }
};
