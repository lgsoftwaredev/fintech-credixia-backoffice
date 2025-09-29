<?php

namespace App\Services\Api;

use App\Models\User;
use Carbon\Carbon;

class LoanSimulationService
{
    public function __construct(private RuleSetService $ruleSetService) {}

    public function simulate(User $user, float $amount, int $termDays, ?string $purpose = null): array
    {
        $rules = $this->ruleSetService->getActive();

        // Validaciones dinámicas contra RuleSet
        if ($amount > $rules->initial_max_amount) {
            abort(422, 'El monto supera el máximo permitido para tu perfil.');
        }
        if ($termDays < $rules->min_term_days || $termDays > $rules->max_term_days) {
            abort(422, 'El plazo está fuera del rango permitido.');
        }

        // Interés simple (MVP): TNA * días/365
        $tna = (float)$rules->base_interest_rate; // e.g. 0.15
        $lateMonthly = (float)$rules->late_interest_rate; // e.g. 0.03
        $interest = round($amount * ($tna) * ($termDays / 365), 2);

        // CAT estimado (simplificado)
        $cat = round($tna, 4); // ej. aprox con factor simple para MVP

        $toPay = round($amount + $interest, 2);

        $firstPayment = Carbon::now()->addMonth()->startOfDay();
        $maturity = Carbon::now()->addDays($termDays)->startOfDay();

        return [
            'amount' => $amount,
            'term_days' => $termDays,
            'rates' => [
                'base' => $tna,
                'late_monthly' => $lateMonthly,
                'cat' => $cat,
            ],
            'fees' => [ 'origination' => 0 ],
            'totals' => [
                'interest' => $interest,
                'to_pay' => $toPay,
            ],
            'dates' => [
                'first_payment' => $firstPayment->toDateString(),
                'maturity' => $maturity->toDateString(),
            ],
            'policy_version' => "ruleset:{$rules->version}",
        ];
    }
}
