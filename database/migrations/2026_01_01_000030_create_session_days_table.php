<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id');
            $table->integer('day_no');
            $table->date('day_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('hall_id')->nullable();
            $table->string('online_url', 500)->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'day_no'], 'session_days_session_id_day_no_unique');
            $table->index(['hall_id', 'day_date', 'start_time', 'end_time'], 'session_days_hall_id_day_date_start_time_end_time_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_days');
    }
};
