<?php

namespace App\Models;


use App\Models\Contracts\AuthenticatableInterface;
use App\Helpers\LogHelper;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;

class GpOperator extends Model implements JWTSubject, AuthenticatableInterface
{
    use HasFactory, Authenticatable;

    protected $fillable = ['name', 'email', 'password', 'cashier', 'cash', 'is_active'];

    protected $hidden = ['password'];

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
        return ['role' => 'operator'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::updating(function ($model) {
            $fieldsToLog = ['cash', 'is_active', 'cashier'];
            $userData = LogHelper::getUserLogData();

            foreach ($fieldsToLog as $field) {
                if ($model->isDirty($field)) {
                    DB::table('gp_operator_balance_logs')->insert([
                        'operator_id' => $model->id,
                        'amount'       => $model->$field,
                        'old_amount'   => $model->getOriginal($field),
                        'new_amount'   => $model->$field,
                        'tag'          => self::getTagForField($field, $model->getOriginal($field), $model->$field),
                        'column'       => $field,
                        'user_id'      => $userData['user_id'],
                        'user_type'    => $userData['user_type'],
                        'created_at'   => now()->timestamp,
                    ]);
                }
            }
        });
    }

    private static function getTagForField($field, $oldValue, $newValue)
    {
        switch ($field) {
            case 'cash':
                if ($newValue > $oldValue) {
                    return 'cash_increase';
                } else {
                    return 'cash_decrease';
                }
            case 'is_active':
                return $newValue ? 'operator_activated' : 'operator_deactivated';
            case 'cashier':
                return $newValue ? 'cashier_enabled' : 'cashier_disabled';
            default:
                return 'field_updated';
        }
    }
}
