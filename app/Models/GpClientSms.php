<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpClientSms extends Model
{
    use HasFactory;

    protected $table = 'gp_client_sms';
    protected $fillable = ['phone', 'code', 'used', 'expires_at'];

    protected $casts = [
        'used' => 'boolean',
        'expires_at' => 'datetime',
    ];
}