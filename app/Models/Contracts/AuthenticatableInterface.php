<?php

namespace App\Models\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface AuthenticatableInterface extends Authenticatable
{
    public function getJWTIdentifier();
    public function getJWTCustomClaims();
}
