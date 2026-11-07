<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainee_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainee_id');
            $table->foreignId('session_id')->nullable();
            $table->enum('warning_type', ['warning', 'issue']);
            $table->text('description')->nullable();
            $table->foreignId('issued_by')->nullable();
            $table->date('issued_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainee_warnings');
    }
};
