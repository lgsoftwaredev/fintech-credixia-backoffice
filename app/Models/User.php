<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Contracts\OAuthenticatable;
use Illuminate\Support\Facades\Cache; // 👈 IMPORTANTE


/**
 * Class User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $kyc_status
 * @property int|null $risk_score
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $phone_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Consent[] $consents
 * @property KycRecord|null $kyc_record
 * @property Collection|Loan[] $loans
 *
 * @package App\Models
 */
class User extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    protected $casts = [
        'risk_score' => 'int',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'kyc_status',
        'risk_score',
        'email_verified_at',
        'phone_verified_at',
        'password',
        'remember_token'
    ];

    public function consents()
    {
        return $this->hasMany(Consent::class);
    }

    public function kyc_record()
    {
        return $this->hasOne(KycRecord::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
    // Permite login por email o phone
    public function findForPassport(string $username): ?self
    {
        return str_contains($username, '@')
            ? $this->where('email', $username)->first()
            : $this->where('phone', $username)->first();
    }

    // Acepta "__otp_verified__" SOLO si existe el flag en cache (y lo consume)
    public function validateForPassportPasswordGrant(string $password): bool
    {
        if ($password === '__otp_verified__') {
            $key = "otp:verified:{$this->id}";
            $ok = Cache::pull($key, false); // obtiene y borra
            \Log::info('validateForPassportPasswordGrant', ['user_id' => $this->id, 'otp_ok' => $ok]);
            return (bool) $ok;
        }

        return Hash::check($password, $this->password);
    }
}
