<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpAdminRefreshToken extends Model
{
    protected $table = 'gp_admin_refresh_tokens';

    protected $fillable = ['admin_id', 'device_id', 'token', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(GpAdmin::class, 'admin_id', 'id');
    }
}
