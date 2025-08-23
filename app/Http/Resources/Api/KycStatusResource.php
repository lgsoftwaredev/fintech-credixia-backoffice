<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class KycStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rec = $this->kyc_record;
        return [
            'user_id'     => $this->id,
            'kyc_status'  => $this->kyc_status,                 // pending|approved|rejected
            'record'      => $rec ? [
                'id'          => $rec->id,
                'provider'    => $rec->provider,
                'result'      => $rec->result,
                'score'       => $rec->score,
                'captured_at' => optional($rec->captured_at)?->toISOString(),
            ] : null,
        ];
    }
}
