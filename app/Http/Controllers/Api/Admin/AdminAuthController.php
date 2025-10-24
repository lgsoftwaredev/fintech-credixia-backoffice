<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AdminAuthTokenResource;
use App\Services\Api\AdminAuthService;
use Illuminate\Http\Request;

class AdminAuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private AdminAuthService $authService) {}

    public function login(Request $request)
    {
        $data = $request->validate([
            'identifier' => 'required|string', // email o phone
            'password'   => 'required|string|min:6',
        ], [
            'identifier.required' => 'El usuario es obligatorio',
            'password.required'   => 'La contraseña es obligatoria',
        ]);

        try {
            $session = $this->authService->login($data['identifier'], $data['password']);
            return $this->success(new AdminAuthTokenResource($session), 'Inicio de sesión exitoso');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 401);
        }
    }

    public function logout(Request $request)
    {
        try {
            $this->authService->logout($request->user());
            return $this->success(null, 'Sesión cerrada correctamente');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
