<?php

namespace App\Services\Api\Kyc;

use App\Models\KycRecord;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class MetaMapProvider implements KycProvider
{
    public function send(KycRecord $record): array
    {
        $cfg = Config::get('services.metamap');

        // 1) Autenticación (si aplica en tu cuenta): token con client_id/secret
        $token = $this->getToken($cfg);

        // 2) Crear verificación en MetaMap asociando flow_id y archivos
        //    (Mapea aquí los endpoints reales de MetaMap para tu flow; placeholder):
        $resp = Http::timeout($cfg['timeout'])
            ->withToken($token)
            ->attach('ine_front',  storage_path("app/{$record->doc_paths['ine_front']}"), 'ine_front.enc')
            ->attach('ine_back',   storage_path("app/{$record->doc_paths['ine_back']}"),  'ine_back.enc')
            ->attach('selfie',     storage_path("app/{$record->selfie_path}"),            'selfie.enc')
            ->post($cfg['base_url'].'/v2/verifications', [
                'flow_id' => $cfg['flow_id'],
                // otros campos del flow (geoloc, metadata):
                'metadata' => [
                    'user_id' => $record->user_id,
                    'lat' => $record->location_lat,
                    'lng' => $record->location_lng,
                    'accuracy_m' => $record->location_accuracy_m,
                ],
            ])->throw();

        // 3) Devuelve IDs que ayuden al callback y trazabilidad
        return [
            'provider' => 'metamap',
            'submitted' => now()->toISOString(),
            'provider_ref' => $resp->json('id') ?? null,
        ];
    }

    private function getToken(array $cfg): string
    {
        // Si tu cuenta usa OAuth con client credentials:
        $tokenResp = Http::asForm()->post($cfg['base_url'].'/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
        ]);

        return data_get($tokenResp->json(), 'access_token', '');
    }
}
