<?php

namespace App\Services\Api;

use App\Models\KycRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class KycService
{
    // app/Services/Api/KycService.php
    public function createProviderSession(User $user, array $data): array
    {
        // 1) Llamar a MetaMap para crear la verificación/sesión (con flow_id)
        // 2) Persistir un KycRecord en pending con provider_ref (verification_id)
        // 3) Devolver token/ids/expiración para que el SDK arranque
        return [
            'flow_id' => config('services.metamap.flow_id'),
            'verification_id' => 'metamap_verif_123',
            'token' => 'eyJhbGciOi...',
            'expires_at' => now()->addMinutes(15)->toISOString(),
            'meta' => ['user_id' => $user->id],
        ];
    }
    public function linkSdkVerification(User $user, array $d): KycRecord
    {
        return DB::transaction(function () use ($user, $d) {

            /** @var KycRecord $rec */
            $rec = KycRecord::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'provider' => 'metamap',
                    'provider_ref' => $d['verification_id'],                // referencia principal
                    'result' => null,                                   // hasta que resolvamos por API/webhook
                    'score' => null,
                    'raw_payload' => array_filter([
                        'identityId' => $d['identity_id'],
                        'flowId' => $d['flow_id'] ?? null,
                        'appStatus' => $d['app_status'] ?? null,                    // completed|cancelled (solo informativo)
                    ]),
                    'location_lat' => $d['lat'] ?? null,
                    'location_lng' => $d['lng'] ?? null,
                    'location_accuracy_m' => $d['accuracy_m'] ?? null,
                    'captured_at' => Carbon::now(),
                ]
            );

            // Usuario queda en revisión
            $user->forceFill(['kyc_status' => 'pending'])->save();

            // Opcional: dispara un Job de polling aquí si lo vas a usar.
            // dispatch(new PollKycStatusJob($rec->id));

            // Aseguramos la relación para la respuesta en el controlador
            $rec->setRelation('user', $user);

            return $rec;
        });
    }
    /**
     * Stores encrypted evidence, creates/updates KycRecord and sets user KYC pending.
     * Dispatches async verification with the provider (job/integration layer).
     */
    public function submit(User $user, array $data, Request $request): KycRecord
    {
        return DB::transaction(function () use ($user, $data, $request) {
            $dir = "kyc/{$user->id}/" . now()->format('Ymd_His') . '_' . Str::random(6);

            $ineFrontPath = $this->storeEncrypted($request->file('ine_front')->getContent(), "$dir/ine_front.enc");
            $ineBackPath = $this->storeEncrypted($request->file('ine_back')->getContent(), "$dir/ine_back.enc");
            $selfiePath = $this->storeEncrypted($request->file('selfie')->getContent(), "$dir/selfie.enc");

            // Upsert record
            $record = KycRecord::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'provider' => $data['provider'],
                    'doc_paths' => ['ine_front' => $ineFrontPath, 'ine_back' => $ineBackPath],
                    'selfie_path' => $selfiePath,
                    'result' => null,
                    'score' => null,
                    'raw_payload' => null,
                    'location_lat' => $data['location_lat'] ?? null,
                    'location_lng' => $data['location_lng'] ?? null,
                    'location_accuracy_m' => $data['location_accuracy_m'] ?? null,
                    'captured_at' => isset($data['captured_at']) ? Carbon::parse($data['captured_at']) : now(),
                ]
            );

            // Put user as pending
            $user->forceFill(['kyc_status' => 'pending'])->save();

            // Here you'd dispatch a Job to send to MetaMap/Truora SDK/API.
            // dispatch(new SendKycToProviderJob($record->id))->onQueue('kyc');

            return $record;
        });
    }

    /**
     * Applies provider result to KycRecord and updates user.kyc_status.
     */
    public function updateFromCallback(User $user, array $data): KycRecord
    {
        return DB::transaction(function () use ($user, $data) {
            /** @var KycRecord $record */
            $record = KycRecord::firstOrCreate(
                ['user_id' => $user->id],
                ['provider' => $data['provider']]
            );

            $record->fill([
                'provider' => $data['provider'],
                'result' => $data['result'],
                'score' => $data['score'] ?? $record->score,
                'raw_payload' => $data['payload'] ?? $record->raw_payload,
            ])->save();

            $statusMap = [
                'approved' => 'approved',
                'rejected' => 'rejected',
                'pending' => 'pending',
            ];
            $user->forceFill(['kyc_status' => $statusMap[$data['result']] ?? 'pending'])->save();

            return $record;
        });
    }

    /**
     * Encrypts binary contents and stores them at local disk.
     */
    private function storeEncrypted(string $binary, string $path): string
    {
        $encrypted = Crypt::encrypt($binary, serialize: true); // binary-safe
        Storage::disk('local')->put($path, $encrypted, 'private');
        return $path;
    }
}
