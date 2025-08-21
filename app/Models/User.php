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
class User extends Authenticatable
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
}
