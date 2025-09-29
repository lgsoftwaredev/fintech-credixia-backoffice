<?php

namespace App\Services\Api;

use App\Http\Resources\Api\LoanResource;
use App\Http\Resources\Api\PaymentResource;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoanService
{
    public function __construct(
        private RuleSetService $ruleSetService,
        private PaymentService $paymentService,
        private ScoringService $scoringService
    ) {
    }

    public function createRequestedLoan(User $user, float $amount, int $termDays, string $purpose): Loan
    {
        $rules = $this->ruleSetService->getActive();

        if ($amount > $rules->initial_max_amount) {
            abort(422, 'El monto supera el máximo permitido para tu perfil.');
        }
        if ($termDays < $rules->min_term_days || $termDays > $rules->max_term_days) {
            abort(422, 'El plazo está fuera del rango permitido.');
        }

        return DB::transaction(function () use ($user, $amount, $termDays, $purpose, $rules) {
            /** @var Loan $loan */
            $loan = Loan::query()->create([
                'user_id' => $user->id,
                'amount' => $amount,
                'interest_rate' => (float) $rules->base_interest_rate,
                'late_interest_rate' => (float) $rules->late_interest_rate,
                'term_days' => $termDays,
                'currency' => 'MXN',
                'cat' => null,
                'status' => 'requested',
                'requested_at' => Carbon::now(),
                'purpose' => $purpose,
                'rules_version' => (string) $rules->version,
            ]);

            return $loan;
        });
    }

    public function processScoring(int $loanId): Loan
    {
        /** @var Loan $loan */
        $loan = Loan::query()->with('user')->findOrFail($loanId);

        if ($loan->status !== 'requested' && $loan->status !== 'under_review') {
            return $loan;
        }

        $result = $this->scoringService->evaluate($loan);

        return DB::transaction(function () use ($loan, $result) {
            $loan->score_snapshot = $result;
            $loan->cat = $loan->interest_rate; // mantener consistencia con simulación
            $loan->approved_at = null;
            $loan->rejected_at = null;
            $loan->rejection_reason = null;

            if ($result['decision'] === 'approved') {
                $loan->status = 'approved';
                $loan->approved_at = now();
            } elseif ($result['decision'] === 'rejected') {
                $loan->status = 'rejected';
                $loan->rejected_at = now();
                $loan->rejection_reason = $result['reason'] ?? 'Solicitud rechazada.';
            } else {
                $loan->status = 'under_review';
            }

            $loan->save();
            if ($result['decision'] === 'approved') {
                $this->paymentService->generateSchedule($loan);
            }

            return $loan;
        });
    }

    public function acceptOffer(Loan $loan): Loan
    {
        if ($loan->status !== 'approved') {
            abort(422, 'La oferta no está disponible para aceptación.');
        }

        return DB::transaction(function () use ($loan) {
            $loan->status = 'disbursed';
            $loan->disbursed_at = now();
            $loan->save();

            // Activa y genera calendario
            $loan->status = 'active';
            $loan->save();

            $this->paymentService->generateSchedule($loan);

            return $loan;
        });
    }
    public function getUserLoans(int $userId, array $filters = [])
    {
        \Log::info('getUserLoans filters', [$userId,$filters]);
        $page = $filters['page'] ?? 1;
        $perPage = $filters['perPage'] ?? 15;

        $query = Loan::where('user_id', $userId)
        ->withCount('payments')
        ->with('payments');

        // Filtro de fechas
        if (!empty($filters['from'])) {
            $query->whereDate('requested_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('requested_at', '<=', $filters['to']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('purpose', 'LIKE', "%{$search}%")
                    ->orWhere('amount', 'LIKE', "%{$search}%");
            });
        }

        // 👉 Si viene el flag, solo cargamos los últimos 2 pagos
        // if (!empty($filters['lastPayments']) && $filters['lastPayments'] == true) {
        //     $query->with([
        //         'payments' => function ($q) {
        //             $q->orderByDesc('created_at')->limit(2);
        //         }
        //     ])->withCount('payments');
        // } else {
        //     $query->with('payments');
        // }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => LoanResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }


    public function getLoanPayments(int $loanId, int $userId, array $filters)
    {
        $page = $filters['page'] ?? 1;
        $perPage = $filters['perPage'] ?? 15;

        $query = Payment::where('loan_id', $loanId)
            ->whereHas('loan', fn($q) => $q->where('user_id', $userId));

        $paginator = $query->orderBy('due_date')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => PaymentResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
