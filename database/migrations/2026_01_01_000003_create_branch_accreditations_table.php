<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_accreditations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id');
            $table->foreignId('accreditation_id');
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['branch_id', 'accreditation_id'], 'branch_accreditations_branch_id_accreditation_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_accreditations');
    }
};
