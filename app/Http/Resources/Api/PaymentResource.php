<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'loan_id'          => $this->loan_id,
            'due_date'    => optional($this->due_date)->toIso8601String(),
            'amount_due'  => $this->amount_due,
            'amount_paid' => $this->amount_paid,
            'status'      => $this->status,
            'channel'     => $this->channel,
            'processor'   => $this->processor,
            'reference'   => $this->reference,
            'paid_at'     => optional($this->paid_at)->toIso8601String(),
            'reconciled_at'=> optional($this->reconciled_at)->toIso8601String(),
        ];
    }
}
