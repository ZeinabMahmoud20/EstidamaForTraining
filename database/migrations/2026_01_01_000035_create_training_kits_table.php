<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_kits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id');
            $table->foreignId('module_id')->nullable();
            $table->foreignId('trainer_id');
            $table->integer('version')->default(1);
            $table->date('due_date')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->enum('status', ['pending', 'draft', 'submitted', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable();
            $table->string('review_notes', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_kits');
    }
};
