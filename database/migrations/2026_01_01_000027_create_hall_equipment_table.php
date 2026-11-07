<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hall_equipment', function (Blueprint $table) {
            $table->foreignId('hall_id');
            $table->foreignId('equipment_id');
            $table->integer('quantity')->default(1);
            $table->primary(['hall_id', 'equipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hall_equipment');
    }
};
