<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scoring_weights', function (Blueprint $table) {
           $table->bigIncrements('id');

            $table->string('version', 64)->unique();
            $table->boolean('is_active')->default(false);

            // Weights 0..1 or 0..100 (use 0..100 for clarity here)
            $table->unsignedTinyInteger('weight_history_of_payments')->default(40);
            $table->unsignedTinyInteger('weight_user_tenure')->default(20);
            $table->unsignedTinyInteger('weight_current_risk')->default(20);
            $table->unsignedTinyInteger('weight_device_trust')->default(10);
            $table->unsignedTinyInteger('weight_kyc')->default(10);

            $table->json('extra')->nullable();

            $table->timestamps();
        });

        // total <= 100 (soft check)
        \DB::statement('ALTER TABLE scoring_weights ADD CONSTRAINT chk_scoring_total CHECK ((weight_history_of_payments + weight_user_tenure + weight_current_risk + weight_device_trust + weight_kyc) <= 100)');
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scoring_weights');
    }
};
