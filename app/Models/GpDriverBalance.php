<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpDriverBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'balance',
    ];
    
    public function driver()
    {
        return $this->belongsTo(GpDriver::class, 'driver_id');
    }
    
}
