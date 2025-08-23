<?php
namespace App\Jobs;

use App\Models\KycRecord;
use App\Services\Api\Kyc\MetaMapProvider;
use App\Services\Api\Kyc\KycProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SendKycToProviderJob implements ShouldQueue
{
    use Queueable;

    public $tries = 5;
    public $backoff = [5, 15, 60, 120, 300]; // reintentos exponenciales

    public function __construct(public int $kycRecordId) {}

    public function handle(): void
    {
        $record = KycRecord::findOrFail($this->kycRecordId);

        /** @var KycProvider $driver */
        $driver = match ($record->provider) {
            'metamap' => app(MetaMapProvider::class),
            default   => app(MetaMapProvider::class),
        };

        $meta = $driver->send($record);

        // Guarda alguna referencia si hace falta
        $record->forceFill([
            'raw_payload' => array_merge($record->raw_payload ?? [], ['submit_meta' => $meta]),
        ])->save();
    }

    public function failed(Throwable $e): void
    {
        // En caso de agotar reintentos: quedará en pending y se puede revisar manualmente (MVP).
    }
}
