<?php

namespace App\Services\Api;

use App\Models\User;

class ProfileService
{
    public function getProfile(User $user): User
    {
        return $user->load(['kyc_record', 'loans']);
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();

        return $user->fresh(['kyc_record', 'loans']);
    }
}
