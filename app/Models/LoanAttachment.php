<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanAttachment extends Model
{
    protected $fillable = ['loan_id', 'path', 'mime', 'size', 'category', 'meta'];

    protected $casts = ['meta' => 'array'];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
