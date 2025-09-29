<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->string('path');                      // ruta en storage
            $table->string('mime', 100)->nullable();     // image/jpeg, application/pdf
            $table->unsignedInteger('size')->nullable(); // KB
            $table->string('category', 50)->nullable();  // comprobante, recibo, etc.
            $table->json('meta')->nullable();            // nombre original, hash, exif
            $table->timestamps();

            $table->index(['loan_id', 'category']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('loan_attachments');
    }
};
