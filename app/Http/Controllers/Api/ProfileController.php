<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ApiResponseTrait;
use Illuminate\Http\Request;
use App\Services\Api\ProfileService;
use App\Http\Resources\Api\ProfileResource;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ProfileService $profileService) {}

    public function show(Request $request)
    {
        $user = $this->profileService->getProfile($request->user());
        return $this->success(new ProfileResource($user), 'Perfil obtenido correctamente');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name'  => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ], [
            'email.email' => 'El email no tiene un formato válido',
            'phone.max'   => 'El teléfono es demasiado largo',
        ]);

        $user = $this->profileService->updateProfile($request->user(), $data);
        return $this->success(new ProfileResource($user), 'Perfil actualizado correctamente6');
    }
}
