<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OtpService
{
    private const PURPOSE_LOGIN = 'login';
    private const PURPOSE_RESET = 'reset';
    private const PURPOSE_REGISTER = 'register';

    private int $length;
    private int $ttlMinutes;
    private string $prefix;

    public function __construct(
        int $length = 0,
        int $ttlMinutes = 0,
        string $prefix = ''
    ) {
        $config = config('authflow');
        $this->length = $length ?: (int) $config['otp_length'];
        $this->ttlMinutes = $ttlMinutes ?: (int) $config['otp_ttl_minutes'];
        $this->prefix = $prefix ?: (string) $config['otp_prefix'];
    }

    public function generate(string $purpose, int $userId): string
    {
        $code = str_pad((string) random_int(0, (10 ** $this->length) - 1), $this->length, '0', STR_PAD_LEFT);
        \Log::info('otp generate',[$this->key($purpose, $userId),$code,now()->addMinutes($this->ttlMinutes)]);
        Cache::put($this->key($purpose, $userId), $code, now()->addMinutes($this->ttlMinutes));
        return $code;
    }

    public function validate(string $purpose, int $userId, string $code): bool
    {
        $key = $this->key($purpose, $userId);
          \Log::info('otp validate',[$key,$code]);
        $stored = Cache::get($key);
          \Log::info('otp validate stored',[$stored]);

        if (!$stored || !hash_equals($stored, $code)) {
            return false;
        }
        Cache::forget($key); // one-time code
        return true;
    }


    // 2) Agrega el accessor:
    public function registerPurpose(): string
    {
        return self::PURPOSE_REGISTER;
    }

    public function loginPurpose(): string
    {
        return self::PURPOSE_LOGIN;
    }
    public function resetPurpose(): string
    {
        return self::PURPOSE_RESET;
    }

    private function key(string $purpose, int $userId): string
    {
        return "{$this->prefix}:{$purpose}:user:{$userId}";
    }
}
