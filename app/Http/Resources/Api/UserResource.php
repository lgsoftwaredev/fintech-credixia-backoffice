<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'kyc_status'  => $this->kyc_status,
            'risk_score'  => $this->risk_score,
            'created_at'  => optional($this->created_at)->toISOString(),
        ];
    }
}
