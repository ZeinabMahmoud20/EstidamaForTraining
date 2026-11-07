<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('action', 50)->nullable();
            $table->string('entity_type', 80)->nullable();
            $table->bigInteger('entity_id')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
