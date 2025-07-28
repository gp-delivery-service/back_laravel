<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpOperatorBalanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'amount',
        'old_amount',
        'new_amount',
        'tag',
        'column',
        'user_id',
        'user_type',
    ];

    public function user()
    {
        return $this->morphTo();
    }

    public function operator()
    {
        return $this->belongsTo(GpOperator::class);
    }
}
