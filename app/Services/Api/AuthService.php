<?php

namespace App\Services\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
// use Nyholm\Psr7\Response; // 👈 importante
// use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request as HttpRequest;


use Illuminate\Support\Facades\App;
class AuthService
{
    public function createUser(array $payload): User
    {
        // Sanitize minimal set
        $attrs = [
            'name' => $payload['name'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'password' => Hash::make($payload['password']),
            'kyc_status' => 'pending',
        ];

        /** @var User $user */
        $user = User::create($attrs);

        return $user;
    }

    /**
     * @param array<int, array{type:string}> $consents e.g. [['type'=>'terms'], ['type'=>'privacy']]
     */
    public function recordConsents(User $user, array $consents, ?string $ip = null, ?string $userAgent = null): void
    {
        $now = now()->toDateTimeString();

        $rows = [];
        foreach ($consents as $c) {
            // avoid junk
            if (!isset($c['type'])) {
                continue;
            }
            $rows[] = [
                'user_id' => $user->id,
                'type' => (string) $c['type'],
                'ip' => $ip,
                'user_agent' => $userAgent,
                'accepted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            \DB::table('consents')->insert($rows);
        }
    }

    public function validateCredentials(string $identifier, string $password): ?User
    {
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }
        return $user;
    }

    /**
     * Issue tokens using Refresh Token grant (proxied to /oauth/token).
     * Returns: ['access_token','refresh_token','expires_in']
     */


    public function issueTokensWithPasswordClient(User $user, array $scopes = ['*']): array
    {
        // payload del password grant
        $params = [
            'grant_type' => 'password',
            'client_id' => env('PASSPORT_PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSPORT_PASSWORD_CLIENT_SECRET'),
            'username' => $user->email ?? $user->phone,
            'password' => '__otp_verified__',
            'scope' => implode(' ', $scopes),
        ];

        // sub-request local a /oauth/token (no red, no ngrok)
        $req = HttpRequest::create('/oauth/token', 'POST', $params);
        $req->headers->set('Content-Type', 'application/x-www-form-urlencoded');

        $resp = app()->handle($req); // procesa el kernel internamente
        $data = json_decode($resp->getContent(), true);

        if (!empty($data['access_token'])) {
            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in' => $data['expires_in'] ?? (config('authflow.access_token_ttl_minutes') * 60),
            ];
        }

        // fallback: PAT
        $token = $user->createToken('mobile', $scopes);
        return [
            'access_token' => $token->accessToken,
            'refresh_token' => null,
            'expires_in' => config('authflow.access_token_ttl_minutes') * 60,
        ];
    }







    /**
     * Proxy refresh_token to /oauth/token
     */
    public function refreshUsingOAuth(string $refreshToken): array
    {
        $cfg = config('passport_clients');
        $tokenUrl = url($cfg['token_url']);

        $resp = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $cfg['password_client_id'],
            'client_secret' => $cfg['password_client_secret'],
        ]);

        if (!$resp->ok() || !isset($resp['access_token'])) {
            throw new \RuntimeException('Refresh token inválido o expirado');
        }

        return [
            'access_token' => $resp['access_token'],
            'refresh_token' => $resp['refresh_token'] ?? null,
            'expires_in' => $resp['expires_in'] ?? (config('authflow.access_token_ttl_minutes') * 60),
        ];
    }
}
