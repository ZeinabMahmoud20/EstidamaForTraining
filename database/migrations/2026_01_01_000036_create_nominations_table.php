<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id');
            $table->foreignId('trainee_id');
            $table->enum('source', ['self', 'provider']);
            $table->foreignId('nominated_by_provider_id')->nullable();
            $table->dateTime('application_date')->useCurrent();
            $table->json('application_data')->nullable();
            $table->enum('status', ['submitted', 'under_review', 'approved', 'rejected', 'waitlisted', 'withdrawn'])->default('submitted');
            $table->integer('rank_no')->nullable();
            $table->integer('waitlist_order')->nullable();
            $table->foreignId('decision_by')->nullable();
            $table->dateTime('decision_at')->nullable();
            $table->string('decision_reason', 500)->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'trainee_id'], 'nominations_session_id_trainee_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nominations');
    }
};
