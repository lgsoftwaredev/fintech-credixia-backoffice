<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RuleSet
 * 
 * @property int $id
 * @property string $version
 * @property bool $is_active
 * @property float $base_interest_rate
 * @property float $late_interest_rate
 * @property int $min_term_days
 * @property int $max_term_days
 * @property float $initial_max_amount
 * @property float $good_payer_increment_percent
 * @property bool $allow_second_loan
 * @property array|null $extra
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class RuleSet extends Model
{
	protected $table = 'rule_sets';

	protected $casts = [
		'is_active' => 'bool',
		'base_interest_rate' => 'float',
		'late_interest_rate' => 'float',
		'min_term_days' => 'int',
		'max_term_days' => 'int',
		'initial_max_amount' => 'float',
		'good_payer_increment_percent' => 'float',
		'allow_second_loan' => 'bool',
		'extra' => 'json'
	];

	protected $fillable = [
		'version',
		'is_active',
		'base_interest_rate',
		'late_interest_rate',
		'min_term_days',
		'max_term_days',
		'initial_max_amount',
		'good_payer_increment_percent',
		'allow_second_loan',
		'extra'
	];
}
