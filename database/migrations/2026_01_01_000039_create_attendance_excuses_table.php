<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_excuses', function (Blueprint $table) {
            $table->id();
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->dateTime('submitted_at')->useCurrent();
            $table->foreignId('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('review_notes', 300)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_excuses');
    }
};
