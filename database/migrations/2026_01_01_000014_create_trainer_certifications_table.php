<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id');
            $table->string('title', 200);
            $table->string('issuer', 200)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_certifications');
    }
};
