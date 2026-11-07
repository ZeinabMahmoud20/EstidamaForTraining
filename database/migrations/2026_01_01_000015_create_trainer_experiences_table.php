<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id');
            $table->string('organization', 200)->nullable();
            $table->string('position', 150)->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_experiences');
    }
};
