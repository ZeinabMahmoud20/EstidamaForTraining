<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id');
            $table->foreignId('session_id');
            $table->decimal('total_hours', 6, 2)->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['trainer_id', 'session_id'], 'trainer_payments_trainer_id_session_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_payments');
    }
};
