<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hall_change_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_day_id');
            $table->foreignId('old_hall_id')->nullable();
            $table->foreignId('new_hall_id')->nullable();
            $table->string('reason', 300)->nullable();
            $table->foreignId('changed_by')->nullable();
            $table->dateTime('changed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hall_change_log');
    }
};
