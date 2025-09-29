<?php
namespace Database\Seeders;

use App\Models\ScoringWeight;
use Illuminate\Database\Seeder;

class ScoringWeightSeeder extends Seeder
{
    public function run(): void
    {
        // Desactivar cualquier versión previa
        ScoringWeight::query()->update(['is_active' => false]);

        // Versión base de pesos
        ScoringWeight::query()->create([
            'version'                       => 'v1.0-mx',
            'is_active'                     => true,
            'weight_history_of_payments'    => 40, // historial de pagos
            'weight_user_tenure'            => 15, // antigüedad usuario
            'weight_current_risk'           => 25, // señalizaciones de riesgo actuales
            'weight_device_trust'           => 10, // device fingerprint
            'weight_kyc'                    => 10, // verificación KYC
            'extra'                         => [
                'normalize' => true,
                'max_score' => 100,
            ],
        ]);

        // Variante experimental (inactiva)
        ScoringWeight::query()->create([
            'version'                       => 'v1.1-mx-exp',
            'is_active'                     => false,
            'weight_history_of_payments'    => 35,
            'weight_user_tenure'            => 20,
            'weight_current_risk'           => 25,
            'weight_device_trust'           => 10,
            'weight_kyc'                    => 10,
            'extra'                         => [
                'normalize' => true,
                'max_score' => 100,
                'notes' => 'Más peso a antigüedad',
            ],
        ]);
    }
}
