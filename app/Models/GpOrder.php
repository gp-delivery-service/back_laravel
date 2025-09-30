<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpOrder extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'company_id', 'sum', 'delivery_price', 'delivery_pay', 'client_phone', 'geo_comment', 'district_id', 'street_id', 'second_street_id', 'lat', 'lng', 'archive'];

    public function company()
    {
        return $this->belongsTo(GpCompany::class, 'company_id', 'id');
    }
}
