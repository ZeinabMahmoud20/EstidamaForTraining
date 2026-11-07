<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_course_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id');
            $table->foreignId('course_id');
            $table->boolean('is_approved')->default(false);
            $table->foreignId('approved_by')->nullable();
            $table->date('approved_at')->nullable();
            $table->string('notes', 300)->nullable();
            $table->timestamps();
            $table->unique(['trainer_id', 'course_id'], 'trainer_course_approvals_trainer_id_course_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_course_approvals');
    }
};
