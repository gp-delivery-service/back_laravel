<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpCompanyTransaction extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'type',
        'amount',
        'operator_id',
        'admin_id',
        'company_id',
        'created_at',
        'hash',
    ];

    public function generateHash(): string
    {
        $data = implode('|', [
            $this->id,
            $this->type,
            $this->amount,
            $this->company_id,
            $this->operator_id ?? '',
            $this->admin_id ?? '',
            $this->created_at,
        ]);

        return hash('sha256', $data . env('APP_KEY'));
    }


    public function validateHash(): bool
    {
        return $this->hash === $this->generateHash();
    }
}
