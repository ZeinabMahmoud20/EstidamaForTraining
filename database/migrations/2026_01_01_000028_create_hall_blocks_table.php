<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hall_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hall_id');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->enum('reason', ['maintenance', 'reserved', 'other'])->nullable();
            $table->string('notes', 300)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hall_blocks');
    }
};
