<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainee_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id');
            $table->foreignId('session_day_id');
            $table->dateTime('check_in_at')->nullable();
            $table->foreignId('check_in_by')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->foreignId('check_out_by')->nullable();
            $table->enum('status', ['present', 'late', 'early_leave', 'absent_excused', 'absent_unexcused'])->nullable();
            $table->integer('late_minutes')->nullable();
            $table->foreignId('excuse_id')->nullable();
            $table->string('notes', 300)->nullable();
            $table->timestamps();
            $table->unique(['enrollment_id', 'session_day_id'], 'trainee_attendance_enrollment_id_session_day_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainee_attendance');
    }
};
