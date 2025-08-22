<?php

return [
    // OTP
    'otp_length' => 6,
    'otp_ttl_minutes' => 50,
    'otp_prefix' => 'otp', // cache key prefix

    // Rate limits (documentadas)
    'login_attempts_per_min' => 5,

    // Tokens
    'access_token_ttl_minutes' => 15,
    'refresh_token_ttl_days' => 30,
];
