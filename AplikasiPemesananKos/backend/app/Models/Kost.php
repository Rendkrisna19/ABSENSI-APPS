<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kost extends Model
{
    protected $fillable = [
      'name','slug','city','address','price_per_month','rating','review_count',
      'available_rooms','thumbnail','facilities','property_rules','location_detail','map_embed'
    ];

    protected $casts = [
      'facilities' => 'array',
    ];
}
