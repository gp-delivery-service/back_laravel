<?php

namespace App\Models;

use App\Models\Contracts\AuthenticatableInterface;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;

class GpClient extends Model implements JWTSubject, AuthenticatableInterface
{
    use HasFactory, Authenticatable;

    protected $fillable = ['name', 'phone', 'wallet', 'fcm_token'];
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return ['role' => 'client'];
    }
}
