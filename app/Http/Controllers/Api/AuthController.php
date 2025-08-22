<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AuthTokenResource;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Notifications\OtpNotification;
use App\Services\Api\AuthService;
use App\Services\Api\OtpService;
use App\Services\Api\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

// Usa tu trait unificado de respuestas
use App\Http\Controllers\Api\ApiResponseTrait;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private AuthService $authService,
        private OtpService $otpService,
        private PasswordResetService $passwordResetService
    ) {
    }

    /**
     * S1-02 · Paso 1: valida credenciales y envía OTP.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'], // email o phone
            'password' => ['required', 'string', 'min:6'],
        ], [
            'identifier.required' => 'Debes ingresar tu correo o teléfono.',
            'password.required' => 'Debes ingresar tu contraseña.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $user = $this->authService->validateCredentials($data['identifier'], $data['password']);
        if (!$user) {
            return $this->error('Credenciales inválidas', 401);
        }

        // Genera y almacena OTP (5 min)
        $code = $this->otpService->generate($this->otpService->loginPurpose(), $user->id);

        // Aquí integrarías SMS/Email (NotificationService) — por ahora solo log
        Log::info('OTP generado para login', ['user_id' => $user->id, 'code' => $code]);
        $user->notify(new OtpNotification($user, $code, 'login'));

        return $this->success([
            'next_step' => 'otp',
            'ttl_minutes' => config('authflow.otp_ttl_minutes'),
            'user_hint' => [
                'email_masked' => $user->email ? $this->maskEmail($user->email) : null,
                'phone_masked' => $user->phone ? $this->maskPhone($user->phone) : null,
            ],
        ], 'OTP enviado');
    }

    /**
     * S1-02 · Paso 2: verifica OTP y emite tokens.
     */
    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'otp' => ['required', 'string', 'size:6'],
            'purpose' => ['required', 'string', 'in:login,register'],

        ], [
            'user_id.required' => 'Falta el usuario.',
            'user_id.exists' => 'Usuario no encontrado.',
            'otp.required' => 'Debes ingresar el código OTP.',
            'otp.size' => 'El código OTP debe tener 6 dígitos.',
            'purpose.required' => 'Debes indicar el propósito del OTP.',
            'purpose.in' => 'El propósito debe ser login o register.',
        ]);

        $user = User::find($data['user_id']);
        $purpose = $data['purpose'] === 'login'
            ? $this->otpService->loginPurpose()
            : $this->otpService->registerPurpose();

        $valid = $this->otpService->validate($purpose, $user->id, $data['otp']);
        if (!$valid) {
            return $this->error('OTP inválido o expirado', 422);
        }
        // ✅ Marca el OTP como verificado: habilita un único intento de password grant
        Cache::put("otp:verified:{$user->id}", true, now()->addMinutes(config('authflow.otp_ttl_minutes', 5)));

        // Emite tokens
        $tokens = $this->authService->issueTokensWithPasswordClient($user);

        // Opcional: autenticar contexto actual
        Auth::login($user);

        $resource = new AuthTokenResource([
            'user' => $user,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in'],
            'scopes' => ['read:*', 'write:*'],
        ]);

        return $this->success($resource, 'Sesión iniciada correctamente');
    }

    /**
     * S1-02.01 · Forgot: dispara OTP a canal elegido.
     */
    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
        ], [
            'identifier.required' => 'Debes ingresar tu correo o teléfono.',
        ]);

        $user = $this->passwordResetService->findUserByIdentifier($data['identifier']);
        if (!$user) {
            // No revelar existencia: respuesta genérica
            return $this->success(null, 'Si existe una cuenta, se envió un código de verificación');
        }

        $code = $this->otpService->generate($this->otpService->resetPurpose(), $user->id);
        Log::info('OTP generado para reset', ['user_id' => $user->id, 'code' => $code]);

        return $this->success([
            'next_step' => 'reset',
            'ttl_minutes' => config('authflow.otp_ttl_minutes'),
            'user_hint' => [
                'email_masked' => $user->email ? $this->maskEmail($user->email) : null,
                'phone_masked' => $user->phone ? $this->maskPhone($user->phone) : null,
            ],
        ], 'Si existe una cuenta, se envió un código de verificación');
    }

    /**
     * S1-02.01 · Reset: valida OTP y setea nueva contraseña.
     */
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'otp' => ['required', 'string', 'size:6'],
            'new_password' => ['required', 'string', 'min:8'],
        ], [
            'user_id.required' => 'Falta el usuario.',
            'user_id.exists' => 'Usuario no encontrado.',
            'otp.required' => 'Debes ingresar el código OTP.',
            'otp.size' => 'El código OTP debe tener 6 dígitos.',
            'new_password.required' => 'Debes ingresar la nueva contraseña.',
            'new_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $user = User::find($data['user_id']);
        $valid = $this->otpService->validate($this->otpService->resetPurpose(), $user->id, $data['otp']);
        if (!$valid) {
            return $this->error('OTP inválido o expirado', 422);
        }

        $this->passwordResetService->setNewPassword($user, $data['new_password']);

        // Revocación de tokens anteriores (Passport): cerrar sesiones existentes
        // Nota: Implementa tu revocación centralizada si manejas múltiples tokens/clients
        // $user->tokens()->delete();

        return $this->success(new UserResource($user), 'Contraseña actualizada correctamente');
    }

    /**
     * S1-03 · Refresh silencioso: proxy a /oauth/token
     */
    public function refresh(Request $request)
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ], [
            'refresh_token.required' => 'Falta el refresh token.',
        ]);

        try {
            $tokens = $this->authService->refreshUsingOAuth($data['refresh_token']);
        } catch (\Throwable $e) {
            return $this->error('Refresh token inválido o expirado', 401);
        }

        return $this->success([
            'token_type' => 'Bearer',
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in'],
        ], 'Token renovado');
    }

    public function me(Request $request)
    {
        return $this->success(new UserResource($request->user()), 'Perfil');
    }
    // app/Http/Controllers/Api/AuthController.php

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // Consentimientos: al menos términos y privacidad
            'consents' => ['required', 'array', 'min:1'],
            'consents.*.type' => ['required', 'string', 'in:terms,privacy'],
        ], [
            'password.required' => 'Debes ingresar una contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'email.email' => 'El correo no es válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'phone.unique' => 'Este teléfono ya está registrado.',
            'consents.required' => 'Debes aceptar los consentimientos.',
            'consents.*.type.in' => 'Tipo de consentimiento inválido.',
        ]);

        // Al menos uno de los dos: email o phone
        if (empty($data['email']) && empty($data['phone'])) {
            return $this->error('Debes proporcionar email o teléfono.', 422);
        }

        // Crear usuario (hash, kyc_status: pending)
        $user = $this->authService->createUser([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
        ]);

        // Registrar consentimientos (ip, user_agent, accepted_at=now)
        $this->authService->recordConsents(
            user: $user,
            consents: $data['consents'],
            ip: $request->ip(),
            userAgent: (string) $request->userAgent()
        );

        // Generar OTP de registro (válido ~5min)
        $code = $this->otpService->generate($this->otpService->registerPurpose(), $user->id);

        // Aquí enviarías por SMS/Email el OTP (NotificationService).
        $user->notify(new OtpNotification($user, $code, 'register'));


        return $this->success([
            'user_id' => $user->id,
            'next_step' => 'otp_verification',
            'ttl_minutes' => config('authflow.otp_ttl_minutes'),
            'user_hint' => [
                'email_masked' => $user->email ? $this->maskEmail($user->email) : null,
                'phone_masked' => $user->phone ? $this->maskPhone($user->phone) : null,
            ],
        ], 'Usuario registrado, OTP enviado');
    }

    // Helpers (no hacen IO)
    private function maskEmail(?string $email): ?string
    {
        if (!$email || !str_contains($email, '@'))
            return $email;
        [$name, $domain] = explode('@', $email, 2);
        $nameMask = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 0));
        return "{$nameMask}@{$domain}";
    }
    private function maskPhone(?string $phone): ?string
    {
        if (!$phone)
            return $phone;
        $len = strlen($phone);
        return str_repeat('*', max($len - 4, 0)) . substr($phone, -4);
    }
}
