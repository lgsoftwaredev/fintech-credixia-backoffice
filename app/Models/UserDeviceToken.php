<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserDeviceToken
 *
 * @property int $id
 * @property int $user_id
 * @property string $fcm_token
 * @property string|null $device_info
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User $user
 */
class UserDeviceToken extends Model
{
    protected $table = 'user_device_tokens';

    protected $casts = [
        'user_id' => 'int',
    ];

    protected $hidden = [
        'fcm_token',
    ];

    protected $fillable = [
        'user_id',
        'fcm_token',
        'device_info',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
