<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpAdminBalanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'amount',
        'old_amount',
        'new_amount',
        'tag',
        'column',
        'user_id',
        'user_type'
    ];

    public function admin()
    {
        return $this->belongsTo(GpAdmin::class, 'admin_id');
    }
}
