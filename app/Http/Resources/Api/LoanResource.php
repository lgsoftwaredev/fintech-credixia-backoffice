<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'amount' => $this->amount,
            'interest_rate' => $this->interest_rate,
            'late_interest_rate' => $this->late_interest_rate,
            'term_days' => $this->term_days,
            'currency' => $this->currency,
            'cat' => $this->cat,
            'rules_version' => $this->rules_version,
            'requested_at' => optional($this->requested_at)->toIso8601String(),
            'approved_at' => optional($this->approved_at)->toIso8601String(),
            'rejected_at' => optional($this->rejected_at)->toIso8601String(),
            'disbursed_at' => optional($this->disbursed_at)->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'purpose' => $this->purpose,
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(fn($a) => [
                    'id' => $a->id,
                    'path' => $a->path,
                    'category' => $a->category,
                    'mime' => $a->mime
                ]);
            }),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'payments_count' => $this->when(isset($this->payments_count), $this->payments_count),

        ];
    }
}
