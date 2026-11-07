<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id');
            $table->decimal('amount', 12, 2);
            $table->string('method', 50)->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('received_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
