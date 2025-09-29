<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ApiResponseTrait;
use App\Http\Resources\Api\LoanSimulationResource;
use App\Services\Api\LoanSimulationService;
use Illuminate\Http\Request;

class LoanSimulationController extends Controller
{
    use ApiResponseTrait;

    public function simulate(Request $request, LoanSimulationService $service)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'term_days' => ['required', 'integer', 'min:1'],
            'purpose' => ['nullable', 'string', 'max:100'],
        ], [
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser numérico.',
            'term_days.required' => 'El plazo es obligatorio.',
            'term_days.integer' => 'El plazo debe ser un número entero de días.',
        ]);

        $dto = $service->simulate(
            user: $request->user(),
            amount: (float)$data['amount'],
            termDays: (int)$data['term_days'],
            purpose: $data['purpose'] ?? null
        );

        return $this->success(new LoanSimulationResource($dto), 'Simulación calculada correctamente');
    }
}
