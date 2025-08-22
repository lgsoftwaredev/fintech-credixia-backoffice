<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $to, string $message): void
    {
        // Placeholder: do nothing for now.
        Log::info('SMSService placeholder: not sending', compact('to', 'message'));
    }
}
