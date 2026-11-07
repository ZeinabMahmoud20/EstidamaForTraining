<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id');
            $table->string('session_code', 40)->unique();
            $table->foreignId('provider_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('accreditation_id')->nullable();
            $table->enum('delivery_mode', ['onsite', 'online', 'hybrid']);
            $table->string('online_url', 500)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('registration_open')->nullable();
            $table->date('registration_close')->nullable();
            $table->integer('max_trainees')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->enum('status', ['draft', 'open_for_registration', 'closed_registration', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sessions');
    }
};
