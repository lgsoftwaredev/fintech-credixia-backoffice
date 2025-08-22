<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // FCM token del dispositivo
            $table->string('fcm_token', 512);

            // Info del dispositivo (json o string)
            $table->text('device_info')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['user_id']);
            $table->unique(['fcm_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_device_tokens');
    }
};
