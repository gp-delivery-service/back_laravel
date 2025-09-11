<?php

namespace App\Models;

use App\Constants\GpPickupStatus;
use App\Helpers\LogHelper;
use App\Services\NodeService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GpPickup extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'company_id',
        'status',
        'note',
        'system_note',
        'preparing_time',
        'closed_time',
        'archived',
        'search_started_at',
        'picked_up_at',
        'closed_at',
    ];

    protected $casts = [
        'status' => GpPickupStatus::class,
        'search_started_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(GpDriver::class, 'driver_id');
    }

    public function company()
    {
        return $this->belongsTo(GpCompany::class, 'company_id');
    }

    public function orders()
    {
        return $this->hasMany(GpPickupOrder::class, 'pickup_id');
    }

    protected static function booted(): void
    {
        static::updating(function ($model) {
            $fieldsToLog = ['status', 'note', 'system_note', 'picked_up_at', 'closed_at', 'driver_id', 'search_started_at'];
            $userData = LogHelper::getUserLogData();

            foreach ($fieldsToLog as $field) {
                if ($model->isDirty($field)) {
                    DB::table('gp_pickup_logs')->insert([
                        'pickup_id' => $model->id,
                        'field' => $field,
                        'old_value' => $model->getOriginal($field),
                        'new_value' => $model->$field,
                        'user_id' => $userData['user_id'],
                        'user_type' => $userData['user_type'],
                        'created_at' => now()->timestamp,
                    ]);
                }
            }
        });        
    }
}
