<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpMapStreetGeo extends Model
{
    use HasFactory;
    public $timestamps = false;
    
    protected $fillable = [
        'street_id',
        'order',
        'lat',
        'lng',
    ];

    public function street()
    {
        return $this->belongsTo(GpMapStreet::class, 'street_id');
    }

}
