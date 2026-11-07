<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('halls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id');
            $table->string('code', 30);
            $table->string('name', 150);
            $table->integer('capacity');
            $table->string('floor', 30)->nullable();
            $table->string('location_description', 300)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['branch_id', 'code'], 'halls_branch_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('halls');
    }
};
