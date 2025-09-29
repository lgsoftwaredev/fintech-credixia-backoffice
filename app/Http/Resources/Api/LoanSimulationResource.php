<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class LoanSimulationResource extends JsonResource
{
    public function toArray($request)
    {
        // $this es un array/DTO
        return [
            'amount' => $this['amount'],
            'term_days' => $this['term_days'],
            'rates' => $this['rates'],      // base, late_monthly, cat
            'fees' => $this['fees'],        // origination, otros
            'totals' => $this['totals'],    // interest, to_pay
            'dates' => $this['dates'],      // first_payment, maturity
            'policy_version' => $this['policy_version'],
        ];
    }
}
