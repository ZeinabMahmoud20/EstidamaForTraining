<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id');
            $table->foreignId('criteria_type_id');
            $table->enum('operator', ['eq', 'gte', 'lte', 'in', 'not_in']);
            $table->string('value', 200)->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_criteria');
    }
};
