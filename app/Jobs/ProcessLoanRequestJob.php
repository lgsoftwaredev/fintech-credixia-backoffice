<?php

namespace App\Jobs;

use App\Models\Loan;
use App\Notifications\LoanApprovedNotification;
use App\Notifications\LoanRejectedNotification;
use App\Services\Api\LoanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessLoanRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 20;

    public function __construct(public int $loanId) {}

    public function handle(LoanService $loanService): void
    {
        /** @var Loan $loan */
        $loan = $loanService->processScoring($this->loanId);

        $user = $loan->user;

        if ($loan->status === 'approved') {
            $user->notify(new LoanApprovedNotification($loan));
        } elseif ($loan->status === 'rejected') {
            $user->notify(new LoanRejectedNotification($loan));
        }
        // under_review: sin notificar en MVP (quedará refrescando)
    }
}
