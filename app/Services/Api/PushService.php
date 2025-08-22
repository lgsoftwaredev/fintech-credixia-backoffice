<?php

namespace App\Services\Api;

use App\Models\UserDeviceToken;
use App\Services\FirebaseService;

class PushService
{
    public function __construct(
        private readonly FirebaseService $firebase
    ) {}

    /**
     * Get all tokens for a user.
     */
    public function resolveTokensForUser(int $userId): array
    {
        return UserDeviceToken::query()
            ->where('user_id', $userId)
            ->pluck('fcm_token')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Send to a single user (multicast to all their devices).
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        $tokens = $this->resolveTokensForUser($userId);
        if (empty($tokens)) {
            return;
        }
        $this->firebase->sendPushNotification($tokens, $title, $body, $data);
    }

    /**
     * Send to a raw list of tokens (already resolved).
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): void
    {
        if (empty($tokens)) {
            return;
        }
        $this->firebase->sendPushNotification($tokens, $title, $body, $data);
    }
}
