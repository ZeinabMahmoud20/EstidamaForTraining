<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id');
            $table->foreignId('module_id')->nullable();
            $table->string('name', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_banks');
    }
};
