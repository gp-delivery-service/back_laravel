<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'order_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
        'is_sent',
        'sent_at',
        'fcm_token'
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'is_sent' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(GpClient::class, 'client_id');
    }

    public function order()
    {
        return $this->belongsTo(GpOrder::class, 'order_id');
    }

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    public function markAsSent()
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now()
        ]);
    }
}
