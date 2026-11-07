<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('title', 250);
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->text('content_outline')->nullable();
            $table->text('target_audience')->nullable();
            $table->foreignId('category_id')->nullable();
            $table->foreignId('course_type_id')->nullable();
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->nullable();
            $table->decimal('total_hours', 6, 2)->nullable();
            $table->integer('days_count')->nullable();
            $table->enum('default_delivery_mode', ['onsite', 'online', 'hybrid'])->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('min_trainees')->nullable();
            $table->integer('max_trainees')->nullable();
            $table->decimal('min_attendance_pct', 5, 2)->default(75);
            $table->decimal('pass_score_pct', 5, 2)->default(60);
            $table->json('extra_details')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
