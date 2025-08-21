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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('source', 64);         // conekta|stp|mp
            $table->string('event_type', 128)->nullable();
            $table->string('event_id', 128)->unique(); // idempotency key per provider event

            $table->json('payload');
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();

            $table->string('status', 32)->default('received'); // received|processed|failed
            $table->string('error_message', 255)->nullable();

            $table->timestamps();

            $table->index(['source', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
