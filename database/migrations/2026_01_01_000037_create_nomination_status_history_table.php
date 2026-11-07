<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomination_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nomination_id');
            $table->enum('from_status', ['submitted', 'under_review', 'approved', 'rejected', 'waitlisted', 'withdrawn'])->nullable();
            $table->enum('to_status', ['submitted', 'under_review', 'approved', 'rejected', 'waitlisted', 'withdrawn']);
            $table->foreignId('changed_by')->nullable();
            $table->dateTime('changed_at')->useCurrent();
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomination_status_history');
    }
};
