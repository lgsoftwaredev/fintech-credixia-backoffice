<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Consent
 * 
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon $accepted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 *
 * @package App\Models
 */
class Consent extends Model
{
	protected $table = 'consents';

	protected $casts = [
		'user_id' => 'int',
		'accepted_at' => 'datetime'
	];

	protected $fillable = [
		'user_id',
		'type',
		'ip',
		'user_agent',
		'accepted_at'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
