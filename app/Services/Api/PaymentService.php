<?php

namespace App\Services\Api;

use App\Models\Loan;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * MVP: cuotas iguales mensuales aproximadas (días/30).
     */
    public function generateSchedule(Loan $loan): void
    {
        $months = max(1, (int)ceil($loan->term_days / 30));
        $principal = $loan->amount;
        $interest = round($loan->amount * $loan->interest_rate * ($loan->term_days / 365), 2);
        $total = round($principal + $interest, 2);
        $installment = round($total / $months, 2);

        DB::transaction(function () use ($loan, $months, $installment) {
            for ($i = 1; $i <= $months; $i++) {
                Payment::query()->create([
                    'loan_id' => $loan->id,
                    'due_date' => Carbon::now()->addMonths($i)->startOfDay(),
                    'amount_due' => $installment,
                    'amount_paid' => 0,
                    'status' => 'pending',
                    'channel' => 'spei',
                    'processor' => null,
                    'reference' => null,
                ]);
            }
        });
    }
}
