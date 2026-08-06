<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'user_id',
        'kost_id',
        'tenant_name',
        'tenant_phone',
        'tenant_type', // 'self', 'family', 'friend', 'partner'
        'start_date',
        'duration_months',
        'end_date',
        'total_price',
        'stopped_at',
        'refund_amount',
        'penalty_amount',
        'status', // 'active', 'completed', 'stopped', 'cancelled'
    ];

    // Konversi tipe data otomatis
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'stopped_at' => 'date',
        'total_price' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
    ];

    /**
     * Relasi ke User (Pemesan)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Kost
     */
    public function kost()
    {
        return $this->belongsTo(Kost::class);
    }
}