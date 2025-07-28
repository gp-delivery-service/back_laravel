<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpCompanyBalanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
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

    public function company()
    {
        return $this->belongsTo(GpCompany::class);
    }
}
