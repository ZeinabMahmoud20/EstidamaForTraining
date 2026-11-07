<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 80);
            $table->bigInteger('entity_id');
            $table->string('file_name', 250);
            $table->string('file_path', 500);
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->nullable();
            $table->dateTime('uploaded_at')->useCurrent();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id'], 'attachments_entity_type_entity_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
