<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpOperatorRefreshToken extends Model
{
    protected $table = 'gp_operator_refresh_tokens';

    protected $fillable = ['operator_id', 'device_id', 'token', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function operator()
    {
        return $this->belongsTo(GpOperator::class, 'operator_id', 'id');
    }
}
