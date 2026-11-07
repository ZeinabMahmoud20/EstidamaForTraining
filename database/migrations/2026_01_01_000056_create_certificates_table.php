<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->unique();
            $table->foreignId('template_id')->nullable();
            $table->string('certificate_no', 50)->unique();
            $table->enum('certificate_type', ['attendance', 'completion', 'appreciation']);
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['issued', 'revoked'])->default('issued');
            $table->string('verification_code', 60)->nullable()->unique();
            $table->foreignId('issued_by')->nullable();
            $table->foreignId('revoked_by')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->string('revoke_reason', 300)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
