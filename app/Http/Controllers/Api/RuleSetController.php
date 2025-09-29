<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ApiResponseTrait;
use App\Services\Api\RuleSetService;
use Illuminate\Http\JsonResponse;

class RuleSetController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected RuleSetService $ruleSetService
    ) {}

    /**
     * Listar todos los Rule Sets disponibles.
     */
    public function index(): JsonResponse
    {
        try {
            $ruleSets = $this->ruleSetService->getActive();
            return $this->success(
                $ruleSets,
                'Regla obtenida correctamente.'
            );
        } catch (\Throwable $e) {
            return $this->error(
                'No se pudo obtener la regla',
                500,
            );
        }
    }
}
