<?php

namespace App\Models;

use App\Models\Contracts\AuthenticatableInterface;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;

class GpAdmin extends Model implements JWTSubject, AuthenticatableInterface
{
    use HasFactory, Authenticatable;

    protected $fillable = ['name', 'email', 'password', 'fund', 'fund_dynamic', 'total_earn', 'total_driver_pay'];

    protected $hidden = ['password'];

    protected $appends = ['fund_current'];

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
        return ['role' => 'admin'];
    }

    /**
     * Вычисляет текущий фонд на основе fund_dynamic и credit_balance компаний
     * fund_current = fund_dynamic + gp_companies.credit_balance
     * 
     * Логика: деньги из фонда могут находиться только в:
     * 1. fund_dynamic - доступные деньги для выдачи операторам
     * 2. gp_companies.credit_balance - деньги, переданные операторами компаниям
     */
    public function getFundCurrentAttribute()
    {
        // Получаем fund_dynamic
        $fundDynamic = $this->fund_dynamic ?? 0;
        
        // Сумма всех credit_balance компаний (это деньги из фонда)
        $companiesCreditBalance = GpCompany::sum('credit_balance');
        
        // Текущий фонд = доступные деньги + деньги в кредитах компаний
        return $fundDynamic + $companiesCreditBalance;
    }

    /**
     * Проверяет, сходится ли fund_current с fund
     */
    public function isFundBalanced()
    {
        return abs($this->fund_current - $this->fund) < 0.01; // Учитываем погрешность округления
    }

    /**
     * Получает разницу между fund и fund_current
     */
    public function getFundDifference()
    {
        return $this->fund_current - $this->fund;
    }
}
