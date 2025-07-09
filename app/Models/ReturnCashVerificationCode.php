<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnCashVerificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'code',
        'driver_id',
        'operator_id',
        'amount',
        'created_at',
    ];

    public function driver()
    {
        return $this->belongsTo(GpDriver::class, 'driver_id');
    }

}
