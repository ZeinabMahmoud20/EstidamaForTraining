<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->enum('trainer_type', ['internal', 'external']);
            $table->foreignId('provider_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('national_id', 20)->nullable()->unique();
            $table->string('full_name', 200);
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 300)->nullable();
            $table->string('highest_qualification', 200)->nullable();
            $table->integer('years_experience')->nullable();
            $table->text('bio')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->string('bank_account', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainers');
    }
};
