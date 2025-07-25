<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpDriverBalanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'amount',
        'old_amount',
        'new_amount',
        'tag',
        'column',
    ];
}
