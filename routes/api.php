<?php

use Illuminate\Support\Facades\Route;

// Api Controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConsentController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\LoanSimulationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentWebhookController;

// Admin Controllers
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\LoanController as AdminLoanController;
use App\Http\Controllers\Api\Admin\MetricsController as AdminMetricsController;
use App\Http\Controllers\Api\Admin\RuleSetController as AdminRuleSetController;
use App\Http\Controllers\Api\Admin\ScoringController as AdminScoringController;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
| Versioned REST API. Controllers are thin; business rules live in Services.
| OAuth2 (Passport) with PKCE; RBAC via scopes. All timestamps are UTC.
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public endpoints (no auth)
    |--------------------------------------------------------------------------
    */

    // Auth & OTP (rate-limited)
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1') // 5/min/IP
        ->name('auth.login');

    Route::post('otp/verify', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:5,1')
        ->name('auth.otp.verify');

    // Legal consents (can be collected before or right after login)
    Route::post('consents', [ConsentController::class, 'store'])
        ->name('consents.store');

    // KYC provider callback (MetaMap/Truora) — should be signed/verified
    Route::post('users/{user}/kyc/callback', [KycController::class, 'callback'])
        // ->middleware('verify.kyc.signature') // TODO: add your signature check
        ->name('kyc.callback');

    // Payment webhooks (SPEI/Conekta/STP/MercadoPago) — idempotent handlers
    Route::post('payments/webhook/{provider}', [PaymentWebhookController::class, 'handle'])
        // ->middleware('verify.payment.signature') // TODO: add your signature check
        ->whereIn('provider', ['conekta','stp','mp','oxxo','spei'])
        ->name('payments.webhook.handle');


    /*
    |--------------------------------------------------------------------------
    | Authenticated endpoints (Passport)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:api')->group(function () {

        // Session & profile
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        /*
        |----------------------- KYC -----------------------
        | Submit KYC package and track status
        */
        Route::prefix('kyc')->group(function () {
            Route::post('submit', [KycController::class, 'submit'])
                ->middleware('scopes:write:kyc')
                ->name('kyc.submit');

            Route::get('status', [KycController::class, 'status'])
                ->middleware('scopes:read:kyc')
                ->name('kyc.status');
        });

        /*
        |--------------------- Loans -----------------------
        | Simulation, CRUD (limited), schedule, settlement
        */
        Route::post('loans/simulate', [LoanSimulationController::class, 'simulate'])
            ->middleware('scopes:read:loan')
            ->name('loans.simulate');

        Route::get('loans', [LoanController::class, 'index'])
            ->middleware('scopes:read:loan')
            ->name('loans.index');

        Route::post('loans', [LoanController::class, 'store'])
            ->middleware('scopes:write:loan')
            ->name('loans.store');

        Route::get('loans/{loan}', [LoanController::class, 'show'])
            ->middleware('scopes:read:loan')
            ->name('loans.show');

        // Payment schedule for a loan
        Route::get('loans/{loan}/schedule', [PaymentController::class, 'schedule'])
            ->middleware('scopes:read:payment')
            ->name('loans.schedule');

        // Early settlement (quote & confirm)
        Route::post('loans/{loan}/settlement/quote', [LoanController::class, 'settlementQuote'])
            ->middleware('scopes:read:loan')
            ->name('loans.settlement.quote');

        Route::post('loans/{loan}/settlement/confirm', [LoanController::class, 'settlementConfirm'])
            ->middleware('scopes:write:loan')
            ->name('loans.settlement.confirm');

        /*
        |-------------------- Payments ---------------------
        | Upcoming, detail, SPEI reference, upload evidence
        */
        Route::get('payments', [PaymentController::class, 'index'])
            ->middleware('scopes:read:payment')
            ->name('payments.index');

        Route::get('payments/upcoming', [PaymentController::class, 'upcoming'])
            ->middleware('scopes:read:payment')
            ->name('payments.upcoming');

        Route::get('payments/{payment}', [PaymentController::class, 'show'])
            ->middleware('scopes:read:payment')
            ->name('payments.show');

        // SPEI reference/CLABE for a given scheduled payment
        Route::get('payments/{payment}/reference', [PaymentController::class, 'reference'])
            ->middleware('scopes:read:payment')
            ->name('payments.reference');

        // Upload out-of-app proof (photo/PDF) for reconciliation
        Route::post('payments/{payment}/evidence', [PaymentController::class, 'uploadEvidence'])
            ->middleware('scopes:write:payment')
            ->name('payments.evidence');

        /*
        |------------------ Notifications ------------------
        | In-app notification center
        */
        Route::get('notifications', [NotificationController::class, 'index'])
            ->middleware('scopes:read:notification')
            ->name('notifications.index');

        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])
            ->middleware('scopes:write:notification')
            ->name('notifications.readAll');

        /*
        |---------------------- Consents -------------------
        | Read user consents history (legal traceability)
        */
        Route::get('consents', [ConsentController::class, 'index'])
            ->middleware('scopes:read:consent')
            ->name('consents.index');
    });


    /*
    |--------------------------------------------------------------------------
    | Admin endpoints (RBAC via scopes: admin:*)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:api', 'scopes:admin:*'])->prefix('admin')->group(function () {

        // Users
        Route::get('users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
        Route::get('users/{user}/loans', [AdminUserController::class, 'loans'])->name('admin.users.loans');

        // Loans
        Route::get('loans', [AdminLoanController::class, 'index'])->name('admin.loans.index');
        Route::get('loans/{loan}', [AdminLoanController::class, 'show'])->name('admin.loans.show');
        Route::patch('loans/{loan}/status', [AdminLoanController::class, 'updateStatus'])->name('admin.loans.updateStatus');

        // Metrics dashboard (risk, delinquency, payments)
        Route::get('metrics/dashboard', [AdminMetricsController::class, 'dashboard'])->name('admin.metrics.dashboard');

        // Rule sets (credit policy) — list/create/activate
        Route::get('rule-sets', [AdminRuleSetController::class, 'index'])->name('admin.rules.index');
        Route::post('rule-sets', [AdminRuleSetController::class, 'store'])->name('admin.rules.store');
        Route::patch('rule-sets/{ruleSet}/activate', [AdminRuleSetController::class, 'activate'])->name('admin.rules.activate');

        // Scoring weights — list/create/activate
        Route::get('scoring-weights', [AdminScoringController::class, 'index'])->name('admin.scoring.index');
        Route::post('scoring-weights', [AdminScoringController::class, 'store'])->name('admin.scoring.store');
        Route::patch('scoring-weights/{weight}/activate', [AdminScoringController::class, 'activate'])->name('admin.scoring.activate');
    });
});
