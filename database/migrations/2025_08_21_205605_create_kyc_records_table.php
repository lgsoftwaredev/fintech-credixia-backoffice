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
        Schema::create('kyc_records', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('provider', 64); // MetaMap, Truora, etc.

            // Paths encrypted at rest (handled at app level); store logical references
            $table->json('doc_paths')->nullable();   // INE front/back paths
            $table->string('selfie_path')->nullable();

            // Provider raw result payload + normalized result
            $table->string('result', 32)->nullable(); // e.g. approved/rejected/pending
            $table->unsignedSmallInteger('score')->nullable(); // provider score if available
            $table->json('raw_payload')->nullable();

            // Geolocation captured during KYC (fraud prevention)
            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_lng', 10, 7)->nullable();
            $table->decimal('location_accuracy_m', 8, 2)->nullable();

            $table->timestamp('captured_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id'); // 1-1 with users
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_records');
    }
};
