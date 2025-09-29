<?php

namespace Database\Seeders;

use App\Models\RuleSet;
use Illuminate\Database\Seeder;

class RuleSetSeeder extends Seeder
{
    public function run(): void
    {
        // Desactivar cualquier versión previa
        RuleSet::query()->update(['is_active' => false]);

        // Versión base para MX
        RuleSet::query()->create([
            'version'                       => 'v1.0-mx',
            'is_active'                     => true,
            'base_interest_rate'            => 0.1500, // 15% anual
            'late_interest_rate'            => 0.3600, // 36% anual moratoria
            'min_term_days'                 => 30,
            'max_term_days'                 => 360,
            'initial_max_amount'            => 5000.00,
            'good_payer_increment_percent'  => 0.25,   // +25% por buen pagador
            'allow_second_loan'             => false,
            'extra'                         => [
                'currency' => 'MXN',
                'amortization' => 'SIMPLE', // libre para tu lógica
                'min_cat' => 0.0,
                'notes' => 'Versión inicial productiva México',
            ],
        ]);

        // Ejemplo de versión “staging” (inactiva)
        RuleSet::query()->create([
            'version'                       => 'v1.1-mx-staging',
            'is_active'                     => false,
            'base_interest_rate'            => 0.1700,
            'late_interest_rate'            => 0.4000,
            'min_term_days'                 => 30,
            'max_term_days'                 => 540,
            'initial_max_amount'            => 7000.00,
            'good_payer_increment_percent'  => 0.30,
            'allow_second_loan'             => true,
            'extra'                         => [
                'currency' => 'MXN',
                'amortization' => 'SIMPLE',
                'feature_flags' => ['beta_topups' => true],
            ],
        ]);
    }
}
