<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpMapStreet extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = [
        'name',
    ];

    public function points()
    {
        return $this->hasMany(GpMapStreetGeo::class, 'street_id');
    }

    public function getGeosAttribute()
    {
        return $this->points()->orderBy('order')->get();
    }
}
