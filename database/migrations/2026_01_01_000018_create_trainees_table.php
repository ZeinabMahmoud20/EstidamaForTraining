<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainees', function (Blueprint $table) {
            $table->id();
            $table->string('national_id', 20)->unique();
            $table->string('full_name', 200);
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 300)->nullable();
            $table->foreignId('education_level_id')->nullable();
            $table->string('qualification', 200)->nullable();
            $table->integer('years_experience')->nullable();
            $table->string('job_title', 150)->nullable();
            $table->foreignId('job_grade_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainees');
    }
};
