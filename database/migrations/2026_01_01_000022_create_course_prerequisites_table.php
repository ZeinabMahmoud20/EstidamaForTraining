<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_prerequisites', function (Blueprint $table) {
            $table->foreignId('course_id');
            $table->foreignId('prerequisite_course_id');
            $table->primary(['course_id', 'prerequisite_course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_prerequisites');
    }
};
