<?php

namespace App\Services\Api;

use App\Models\Loan;
use App\Models\ScoringWeight;
use Carbon\Carbon;

class ScoringService
{
    public function evaluate(Loan $loan): array
    {
        // Pesos activos
        /** @var ScoringWeight $w */
        $w = ScoringWeight::query()->where('is_active', true)->latest('id')->first();

        // Señales disponibles (MVP simplificado)
        $user = $loan->user()->with(['loans' => function($q){ $q->where('status', 'active'); }])->first();
        $kycStatus = $user->kyc_status; // approved|pending|rejected
        $userTenureDays = now()->diffInDays($user->created_at ?? now(),true);
        $historyScore = 100; // MVP: sin moras previas → 100
        $tenureScore  = min(100, (int)($userTenureDays / 30) * 10); // +10 por mes (cap 100)

        $riskInverse  = max(0, 100 - (int)($user->risk_score ?? 50)); // menor risk_score → mayor puntaje
        $deviceTrust  = 70; // placeholder hasta que tengas fingerprint
        $kycScore     = $kycStatus === 'approved' ? 100 : ($kycStatus === 'pending' ? 40 : 0);

        // Ponderación
        $total =
            ($historyScore * ($w->weight_history_of_payments ?? 20)) +
            ($tenureScore  * ($w->weight_user_tenure         ?? 20)) +
            ($riskInverse  * ($w->weight_current_risk        ?? 20)) +
            ($deviceTrust  * ($w->weight_device_trust        ?? 20)) +
            ($kycScore     * ($w->weight_kyc                 ?? 20));

        // Normaliza a 0–100
        $maxWeight = ($w->weight_history_of_payments + $w->weight_user_tenure + $w->weight_current_risk + $w->weight_device_trust + $w->weight_kyc) ?: 100;
        $score = round($total / $maxWeight, 2);

        // Umbrales MVP
        $decision = 'under_review';
        $reason   = null;

        if ($kycStatus !== 'approved') {
            $decision = 'under_review';
            $reason = 'KYC pendiente o rechazado';
        } elseif ($score >= 70) {
            $decision = 'approved';
        } elseif ($score < 40) {
            $decision = 'rejected';
            $reason = 'Riesgo elevado según política vigente';
        }

        return [
            'score' => $score,
            'decision' => $decision,
            'reason' => $reason,
            'evaluated_at' => Carbon::now()->toIso8601String(),
            'signals' => [
                'kyc_status' => $kycStatus,
                'user_tenure_days' => $userTenureDays,
                'history_score' => $historyScore,
                'tenure_score' => $tenureScore,
                'risk_inverse' => $riskInverse,
                'device_trust' => $deviceTrust,
            ],
            'weights_version' => optional($w)->version,
        ];
    }
}
