<?php

namespace App\Services\Api;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\PersonalAccessTokenResult;
use Illuminate\Support\Facades\Hash;

class AdminAuthService
{
    public function login(string $identifier, string $password): PersonalAccessTokenResult
    {
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new \Exception('Credenciales inválidas');
        }

        if (!method_exists($user, 'role') && !$user->role) {
            throw new \Exception('Acceso denegado, no es administrador');
        }

        return $user->createToken('admin', ['*']);
    }

    public function logout(User $user): void
    {
        $user->token()->revoke();
    }
}
