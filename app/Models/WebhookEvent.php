<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WebhookEvent
 * 
 * @property int $id
 * @property string $source
 * @property string|null $event_type
 * @property string $event_id
 * @property array $payload
 * @property Carbon $received_at
 * @property Carbon|null $processed_at
 * @property string $status
 * @property string|null $error_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class WebhookEvent extends Model
{
	protected $table = 'webhook_events';

	protected $casts = [
		'payload' => 'json',
		'received_at' => 'datetime',
		'processed_at' => 'datetime'
	];

	protected $fillable = [
		'source',
		'event_type',
		'event_id',
		'payload',
		'received_at',
		'processed_at',
		'status',
		'error_message'
	];
}
