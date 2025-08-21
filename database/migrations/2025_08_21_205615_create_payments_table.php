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
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();

            $table->date('due_date');

            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);

            $table->enum('status', ['scheduled', 'pending', 'paid', 'failed', 'in_review'])
                ->default('scheduled');

            // SPEI / cash (OXXO) / card meta (docs priorizan SPEI)
            $table->string('channel', 32)->default('spei');     // spei|oxxo|card...
            $table->string('processor', 32)->nullable();        // conekta|stp|mp...
            $table->string('reference', 64)->nullable();        // reference shown to user
            $table->string('external_id', 128)->nullable();     // processor payment id
            $table->string('idempotency_key', 128)->nullable(); // webhook idempotency

            $table->string('receipt_url')->nullable();          // link to receipt if provided
            $table->string('evidence_path')->nullable();        // proof upload path (out-of-app)

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reconciled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Performance & uniqueness
            $table->index(['loan_id', 'due_date']);
            $table->unique('idempotency_key');
        });

        \DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_payments_amounts CHECK (amount_due > 0 AND amount_paid >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
