<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ApiResponseTrait;
use App\Http\Resources\Api\LoanResource;
use App\Models\Loan;
use App\Services\Api\LoanService;
use App\Jobs\ProcessLoanRequestJob;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    use ApiResponseTrait;

    public function store(Request $request, LoanService $service)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'term_days' => ['required', 'integer', 'min:1'],
            'purpose' => ['required', 'string', 'max:100'],
            'accept_terms' => ['required', 'accepted'],
        ], [
            'amount.required' => 'El monto es obligatorio.',
            'term_days.required' => 'El plazo es obligatorio.',
            'purpose.required' => 'El motivo del crédito es obligatorio.',
            'accept_terms.accepted' => 'Debes aceptar los términos y condiciones.',
        ]);

        $loan = $service->createRequestedLoan(
            user: $request->user(),
            amount: (float) $data['amount'],
            termDays: (int) $data['term_days'],
            purpose: $data['purpose']
        );

        // Encolar evaluación (scoring) asíncrona
        ProcessLoanRequestJob::dispatch($loan->id);

        return $this->success(new LoanResource($loan), 'Solicitud creada y en evaluación', 201);
    }

    public function acceptOffer(Request $request, Loan $loan, LoanService $service)
    {
        if ($loan->user_id !== $request->user()->id) {
            return $this->error('No tienes permisos para este recurso.', 403);
        }

        $updated = $service->acceptOffer($loan);

        return $this->success(new LoanResource($updated->load('attachments')), 'Oferta aceptada correctamente');
    }
    public function history(Request $request, LoanService $service)
    {
        try {
            $user = $request->user();
            $filters = $request->only(['from', 'to', 'status', 'search', 'page', 'perPage','lastPayments']);
            $result = $service->getUserLoans($user->id, $filters);

            return $this->success(
                [
                    'items' => $result['data'],
                    'meta' => $result['meta'],
                ],
                'Historial de créditos recuperado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->error('Error al obtener historial de créditos', 500, [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function payments(Request $request, $loanId, LoanService $service)
    {
        try {
            $user = $request->user();
            $filters = $request->only(['page', 'perPage']);
            $result = $service->getLoanPayments($loanId, $user->id, $filters);

            return $this->success(
                [
                    'items' => $result['data'],
                    'meta' => $result['meta'],
                ],
                'Pagos recuperados exitosamente'
            );

        } catch (\Exception $e) {
            return $this->error('Error al obtener pagos del préstamo', 500, [
                'exception' => $e->getMessage()
            ]);
        }
    }
}
