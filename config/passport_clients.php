<?php

return [
    // Usa .env y NO publiques los secretos
    'password_client_id' => env('PASSPORT_PASSWORD_CLIENT_ID'),
    'password_client_secret' => env('PASSPORT_PASSWORD_CLIENT_SECRET'),
    'token_url' => env('PASSPORT_TOKEN_URL', '/oauth/token'),
];
