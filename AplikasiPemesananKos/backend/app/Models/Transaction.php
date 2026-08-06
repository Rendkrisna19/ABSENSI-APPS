<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    

    protected $fillable = [
        'user_id',
        'kost_id',
        'start_date',
        'end_date',
        'duration',
        'total_price',
        'external_id',
        'payment_url',
        'status',       
        'rent_status',  
        'tenant_name',
        'tenant_phone',
        'tenant_type',
        
        'stopped_at',
        'refund_amount',
        'penalty_amount',
        'refund_bank_name',      
        'refund_account_number', 
        'refund_account_name',   
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Kost
    public function kost()
    {
        return $this->belongsTo(Kost::class);
    }
}