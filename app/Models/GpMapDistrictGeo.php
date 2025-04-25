<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpMapDistrictGeo extends Model
{
    use HasFactory;
    public $timestamps = false;
    
    protected $fillable = [
        'district_id',
        'order',
        'lat',
        'lng',
    ];

    public function district()
    {
        return $this->belongsTo(GpMapDistrict::class, 'district_id');
    }
}
