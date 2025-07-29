<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpClientBalanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
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

    public function client()
    {
        return $this->belongsTo(GpClient::class);
    }
}