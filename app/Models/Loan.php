<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Loan
 * 
 * @property int $id
 * @property int $user_id
 * @property float $amount
 * @property float $interest_rate
 * @property float|null $late_interest_rate
 * @property int $term_days
 * @property string $currency
 * @property float|null $cat
 * @property array|null $amortization_policy
 * @property string $status
 * @property Carbon|null $requested_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $rejected_at
 * @property string|null $rejection_reason
 * @property Carbon|null $disbursed_at
 * @property string|null $purpose
 * @property array|null $score_snapshot
 * @property string|null $rules_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property User $user
 * @property Collection|Payment[] $payments
 *
 * @package App\Models
 */
class Loan extends Model
{
	use SoftDeletes;
	protected $table = 'loans';

	protected $casts = [
		'user_id' => 'int',
		'amount' => 'float',
		'interest_rate' => 'float',
		'late_interest_rate' => 'float',
		'term_days' => 'int',
		'cat' => 'float',
		'amortization_policy' => 'json',
		'requested_at' => 'datetime',
		'approved_at' => 'datetime',
		'rejected_at' => 'datetime',
		'disbursed_at' => 'datetime',
		'score_snapshot' => 'json'
	];

	protected $fillable = [
		'user_id',
		'amount',
		'interest_rate',
		'late_interest_rate',
		'term_days',
		'currency',
		'cat',
		'amortization_policy',
		'status',
		'requested_at',
		'approved_at',
		'rejected_at',
		'rejection_reason',
		'disbursed_at',
		'purpose',
		'score_snapshot',
		'rules_version'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function payments()
	{
		return $this->hasMany(Payment::class);
	}
}
