<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Payment
 * 
 * @property int $id
 * @property int $loan_id
 * @property Carbon $due_date
 * @property float $amount_due
 * @property float $amount_paid
 * @property string $status
 * @property string $channel
 * @property string|null $processor
 * @property string|null $reference
 * @property string|null $external_id
 * @property string|null $idempotency_key
 * @property string|null $receipt_url
 * @property string|null $evidence_path
 * @property Carbon|null $paid_at
 * @property Carbon|null $reconciled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Loan $loan
 *
 * @package App\Models
 */
class Payment extends Model
{
	use SoftDeletes;
	protected $table = 'payments';

	protected $casts = [
		'loan_id' => 'int',
		'due_date' => 'datetime',
		'amount_due' => 'float',
		'amount_paid' => 'float',
		'paid_at' => 'datetime',
		'reconciled_at' => 'datetime'
	];

	protected $fillable = [
		'loan_id',
		'due_date',
		'amount_due',
		'amount_paid',
		'status',
		'channel',
		'processor',
		'reference',
		'external_id',
		'idempotency_key',
		'receipt_url',
		'evidence_path',
		'paid_at',
		'reconciled_at'
	];

	public function loan()
	{
		return $this->belongsTo(Loan::class);
	}
}
