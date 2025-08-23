<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\KycRecord */
class KycRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'provider'    => $this->provider,
            'result'      => $this->result,         // null until callback
            'score'       => $this->score,
            // Paths are kept internal; we do not expose storage keys.
            'location'    => [
                'lat'       => $this->location_lat,
                'lng'       => $this->location_lng,
                'accuracy_m'=> $this->location_accuracy_m,
            ],
            'captured_at' => optional($this->captured_at)?->toISOString(),
            'created_at'  => optional($this->created_at)?->toISOString(),
        ];
    }
}
