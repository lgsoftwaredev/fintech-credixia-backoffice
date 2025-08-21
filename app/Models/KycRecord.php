<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class KycRecord
 * 
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property array|null $doc_paths
 * @property string|null $selfie_path
 * @property string|null $result
 * @property int|null $score
 * @property array|null $raw_payload
 * @property float|null $location_lat
 * @property float|null $location_lng
 * @property float|null $location_accuracy_m
 * @property Carbon|null $captured_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property User $user
 *
 * @package App\Models
 */
class KycRecord extends Model
{
	use SoftDeletes;
	protected $table = 'kyc_records';

	protected $casts = [
		'user_id' => 'int',
		'doc_paths' => 'json',
		'score' => 'int',
		'raw_payload' => 'json',
		'location_lat' => 'float',
		'location_lng' => 'float',
		'location_accuracy_m' => 'float',
		'captured_at' => 'datetime'
	];

	protected $fillable = [
		'user_id',
		'provider',
		'doc_paths',
		'selfie_path',
		'result',
		'score',
		'raw_payload',
		'location_lat',
		'location_lng',
		'location_accuracy_m',
		'captured_at'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
