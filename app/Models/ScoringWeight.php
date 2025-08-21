<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ScoringWeight
 * 
 * @property int $id
 * @property string $version
 * @property bool $is_active
 * @property int $weight_history_of_payments
 * @property int $weight_user_tenure
 * @property int $weight_current_risk
 * @property int $weight_device_trust
 * @property int $weight_kyc
 * @property array|null $extra
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class ScoringWeight extends Model
{
	protected $table = 'scoring_weights';

	protected $casts = [
		'is_active' => 'bool',
		'weight_history_of_payments' => 'int',
		'weight_user_tenure' => 'int',
		'weight_current_risk' => 'int',
		'weight_device_trust' => 'int',
		'weight_kyc' => 'int',
		'extra' => 'json'
	];

	protected $fillable = [
		'version',
		'is_active',
		'weight_history_of_payments',
		'weight_user_tenure',
		'weight_current_risk',
		'weight_device_trust',
		'weight_kyc',
		'extra'
	];
}
