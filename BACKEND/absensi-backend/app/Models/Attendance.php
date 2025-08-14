<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
      use HasFactory;

        protected $fillable = [
            'user_id',
            'check_in_time',
            'check_in_latitude',
            'check_in_longitude',
            'check_out_time',
            'check_out_latitude',
            'check_out_longitude',
            'status',
            'reason',
        ];

        public function user()
        {
            return $this->belongsTo(User::class);
        }
        public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

}
