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
        Schema::create('rule_sets', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('version', 64)->unique(); // e.g. "v1.0.0-2025-08-21"
            $table->boolean('is_active')->default(false);

            // Parametrizable (docs: monto máximo inicial, tasa base, moratoria, plazos)
            $table->decimal('base_interest_rate', 5, 2);  // %
            $table->decimal('late_interest_rate', 5, 2);  // %
            $table->unsignedInteger('min_term_days');
            $table->unsignedInteger('max_term_days');
            $table->decimal('initial_max_amount', 12, 2); // MXN

            // Buen pagador (mejora de condiciones)
            $table->decimal('good_payer_increment_percent', 5, 2)->default(0); // %

            // Toggles de política
            $table->boolean('allow_second_loan')->default(false);

            $table->json('extra')->nullable(); // extensiones futuras

            $table->timestamps();
        });

        DB::statement('ALTER TABLE rule_sets ADD CONSTRAINT chk_rules_interest CHECK (base_interest_rate >= 0 AND base_interest_rate <= 100)');
        DB::statement('ALTER TABLE rule_sets ADD CONSTRAINT chk_rules_late_interest CHECK (late_interest_rate >= 0 AND late_interest_rate <= 100)');
        DB::statement('ALTER TABLE rule_sets ADD CONSTRAINT chk_rules_terms CHECK (min_term_days > 0 AND max_term_days >= min_term_days)');
        DB::statement('ALTER TABLE rule_sets ADD CONSTRAINT chk_rules_amount CHECK (initial_max_amount > 0)');
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_sets');
    }
};
