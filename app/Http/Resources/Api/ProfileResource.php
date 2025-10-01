<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'kyc_status' => $this->kyc_status,
            'risk_score' => $this->risk_score,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relaciones útiles
            //'kyc'        => $this->whenLoaded('kyc_record'),
           // 'loans'      => $this->whenLoaded('loans'),
        ];
    }
}
