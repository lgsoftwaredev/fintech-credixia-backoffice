<?php
namespace App\Services\Api\Kyc;

use App\Models\KycRecord;

interface KycProvider {
    /** Envía el paquete KYC al proveedor y retorna un array con metadatos (decision/ids). */
    public function send(KycRecord $record): array;
}
