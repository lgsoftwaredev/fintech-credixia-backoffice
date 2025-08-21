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
        Schema::create('loans', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Monetary + terms
            $table->decimal('amount', 12, 2);               // approved/requested amount
            $table->decimal('interest_rate', 5, 2);         // % annual or per policy
            $table->decimal('late_interest_rate', 5, 2)->nullable(); // % moratorio (docs)
            $table->unsignedInteger('term_days');           // duration in days
            $table->string('currency', 3)->default('MXN');

            // Optional transparency fields (computed but persisted for traceability)
            $table->decimal('cat', 5, 2)->nullable();       // Coste Anual Total %
            $table->json('amortization_policy')->nullable();// policy snapshot at origination

            // Lifecycle state (docs define the state machine)
            $table->enum('status', [
                'draft',
                'requested',
                'under_review',
                'approved',
                'rejected',
                'disbursed',
                'active',
                'in_arrears',
                'liquidated'
            ])->default('draft');

            // Decision + audit
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->timestamp('disbursed_at')->nullable();

            // Business context
            $table->string('purpose', 255)->nullable();      // "destino del crédito" (docs)
            $table->json('score_snapshot')->nullable();      // risk/scoring snapshot at decision
            $table->string('rules_version', 64)->nullable(); // active rule set identifier

            $table->timestamps();
            $table->softDeletes();

            // Performance indexes
            $table->index(['user_id', 'status']);
        });
        // CHECK constraints for sanity
        \DB::statement('ALTER TABLE loans ADD CONSTRAINT chk_loans_amount CHECK (amount > 0)');
        \DB::statement('ALTER TABLE loans ADD CONSTRAINT chk_loans_interest CHECK (interest_rate >= 0 AND interest_rate <= 100)');
        \DB::statement('ALTER TABLE loans ADD CONSTRAINT chk_loans_late_interest CHECK (late_interest_rate IS NULL OR (late_interest_rate >= 0 AND late_interest_rate <= 100))');
        \DB::statement('ALTER TABLE loans ADD CONSTRAINT chk_loans_term CHECK (term_days > 0)');
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
