<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpCompanyManagerRefreshToken extends Model
{
    use HasFactory;

    protected $table = 'gp_company_manager_refresh_tokens';

    protected $fillable = ['manager_id', 'device_id', 'token', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function operator()
    {
        return $this->belongsTo(GpCompanyManager::class, 'manager_id', 'id');
    }
}
