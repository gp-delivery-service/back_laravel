<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpSettings extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'int_value'];
    public $timestamps = false;


    public static function driverFee(): int
    {
        return (int) self::query()
            ->where('key', 'driver_fee')
            ->value('int_value') ?? 25;
    }
}
