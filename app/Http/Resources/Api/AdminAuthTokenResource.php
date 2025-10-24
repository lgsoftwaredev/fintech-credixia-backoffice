<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminAuthTokenResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'access_token' => $this->accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => $this->token->expires_at,
            'scope'        => $this->token->scopes,
        ];
    }
}
