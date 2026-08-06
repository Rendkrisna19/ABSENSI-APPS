<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $table = 'chats';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'is_read',
    ];

    // Cast is_read ke boolean agar di JSON jadi true/false (bukan 1/0)
    protected $casts = [
        'is_read' => 'boolean',
    ];

    // Relasi ke Pengirim
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Relasi ke Penerima
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}