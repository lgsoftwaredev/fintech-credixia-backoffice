<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthTokenResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'user' => new UserResource($this->resource['user']),
            'tokens' => [
                'token_type'    => 'Bearer',
                'access_token'  => $this->resource['access_token'],
                'refresh_token' => $this->resource['refresh_token'],
                'expires_in'    => $this->resource['expires_in'], // seconds
            ],
            'scopes' => $this->resource['scopes'] ?? [],
        ];
    }
}
