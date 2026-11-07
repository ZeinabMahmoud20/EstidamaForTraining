<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 40)->unique();
            $table->foreignId('session_id');
            $table->enum('bill_to', ['trainee', 'provider']);
            $table->foreignId('trainee_id')->nullable();
            $table->foreignId('provider_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['draft', 'issued', 'partially_paid', 'paid', 'cancelled'])->default('draft');
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
