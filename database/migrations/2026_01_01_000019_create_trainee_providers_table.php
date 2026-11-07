<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainee_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainee_id');
            $table->foreignId('provider_id');
            $table->string('job_title_at_provider', 150)->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['trainee_id', 'provider_id'], 'trainee_providers_trainee_id_provider_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainee_providers');
    }
};
