<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_forms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('title', 250);
            $table->enum('target_type', ['trainer', 'program', 'trainee', 'venue', 'operations']);
            $table->enum('evaluator_type', ['trainee', 'trainer', 'supervisor', 'provider']);
            $table->boolean('is_anonymous')->default(true);
            $table->boolean('is_system')->default(false);
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_forms');
    }
};
