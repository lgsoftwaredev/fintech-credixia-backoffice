<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ApiResponseTrait;
use App\Http\Resources\Api\KycStatusResource;
use App\Http\Resources\Api\KycRecordResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Services\Api\KycService;

class KycController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly KycService $kycService)
    {
        // Thin controller; business logic lives in the Service.
    }
    // app/Http/Controllers/Api/KycController.php
    public function createSession(Request $request)
    {
        $data = $request->validate([
            'provider' => ['required', Rule::in(['metamap'])],
            'flow_hint' => ['nullable', 'string'], // por si manejás varios flows
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = $request->user();
        $session = $this->kycService->createProviderSession($user, $data);

        return $this->success([
            'provider' => 'metamap',
            'flow_id' => $session['flow_id'],
            'verification_id' => $session['verification_id'], // o identity_id
            'session_token' => $session['token'],
            'expires_at' => $session['expires_at'],
            'meta' => $session['meta'] ?? [],
        ], 'Sesión KYC creada. Inicializa el SDK con estos datos.');
    }

    /**
     * POST /api/v1/kyc/submit (scopes: write:kyc)
     * Submits INE front/back + selfie + geo for KYC.
     */
    public function submit(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'provider' => ['required', Rule::in(['metamap', 'truora'])],
            'ine_front' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'ine_back' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'selfie' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'location_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'location_accuracy_m' => ['nullable', 'numeric', 'min:0'],
            'captured_at' => ['nullable', 'date'],
        ], [
            'provider.required' => 'El proveedor es obligatorio.',
            'provider.in' => 'Proveedor inválido.',
            'ine_front.required' => 'La foto del INE (frente) es obligatoria.',
            'ine_back.required' => 'La foto del INE (reverso) es obligatoria.',
            'selfie.required' => 'La selfie es obligatoria.',
            'ine_front.mimes' => 'Formato inválido. Usa JPG o PNG.',
            'ine_back.mimes' => 'Formato inválido. Usa JPG o PNG.',
            'selfie.mimes' => 'Formato inválido. Usa JPG o PNG.',
        ]);

        $record = $this->kycService->submit($user, $data, $request);

        return $this->success(
            data: new KycRecordResource($record),
            message: 'Evidencias KYC cargadas; validación en curso.'
        );
    }
    /**
     * POST /api/v1/kyc/link
     * Body JSON:
     * {
     *   "provider": "metamap",
     *   "verification_id": "verif_abc123",
     *   "identity_id": "ident_zzz999",
     *   "app_status": "completed|cancelled",         // opcional (estado local del SDK)
     *   "flow_id": "flow_xxx",                       // opcional
     *   "lat": -34.90, "lng": -56.16, "accuracy_m": 12  // opcional
     * }
     */
    public function linkFromSdk(Request $req)
    {
        \Log::info('linkFromSdk',[$req->all()]);
        $data = $req->validate([
            'provider' => ['required', Rule::in(['metamap'])],
            'verification_id' => ['required', 'string', 'max:255'],
            'identity_id' => ['required', 'string', 'max:255'],
            'app_status' => ['nullable', Rule::in(['none', 'pending'])],
            'flow_id' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'numeric', 'min:0'],
        ], [
            'provider.required' => 'El proveedor es obligatorio.',
            'provider.in' => 'Proveedor inválido.',
            'verification_id.required' => 'Falta verification_id.',
            'identity_id.required' => 'Falta identity_id.',
        ]);

        $record = $this->kycService->linkSdkVerification($req->user(), $data);

        return $this->success(
            data: [
                'record_id' => $record->id,
                'user_id' => $record->user_id,
                'kyc_status' => $record->user->kyc_status, // debería ser "pending"
                'provider' => $record->provider,
                'provider_ref' => $record->provider_ref,
                'captured_at' => optional($record->captured_at)?->toISOString(),
            ],
            message: 'Verificación enlazada. KYC en revisión (pending).'
        );
    }
    /**
     * GET /api/v1/kyc/status (scopes: read:kyc)
     */
    public function status(Request $request)
    {
        $user = $request->user()->load('kyc_record');
        return $this->success(new KycStatusResource($user));
    }

    /**
     * POST /api/v1/users/{user}/kyc/callback (public webhook from provider)
     * Body example (generic):
     * {
     *   "provider": "metamap",
     *   "result": "approved|rejected|pending",
     *   "score": 87,
     *   "payload": {...} // raw provider payload
     * }
     */
    public function callback(Request $request, User $user)
    {
        // TODO: signature verification middleware (X-KYC-Signature)
        $data = $request->validate([
            'provider' => ['required', Rule::in(['metamap', 'truora'])],
            'result' => ['required', Rule::in(['approved', 'rejected', 'pending'])],
            'score' => ['nullable', 'integer', 'between:0,100'],
            'payload' => ['nullable', 'array'],
        ], [
            'result.in' => 'Resultado inválido.',
        ]);

        $record = $this->kycService->updateFromCallback($user, $data);

        return $this->success(
            data: new KycRecordResource($record->load('user')),
            message: 'Callback KYC procesado.'
        );
    }
}
