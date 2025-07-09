<?php

namespace App\Models;

use App\Constants\GpPickupOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GpPickupOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'pickup_id',
        'order_id',
        'status',
        'note',
        'system_note',
        'sort_order',
    ];

    protected $casts = [
        'status' => GpPickupOrderStatus::class,
    ];

    protected static function booted(): void
    {
        static::updating(function ($model) {
            $fieldsToLog = ['status', 'note', 'system_note'];

            foreach ($fieldsToLog as $field) {
                if ($model->isDirty($field)) {
                    DB::table('gp_pickup_order_logs')->insert([
                        'pickup_order_id' => $model->id,
                        'field' => $field,
                        'old_value' => $model->getOriginal($field),
                        'new_value' => $model->$field,
                        'created_at' => now()->timestamp,
                    ]);
                }
            }
        });
    }
}
