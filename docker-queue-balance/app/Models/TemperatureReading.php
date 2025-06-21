<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemperatureReading extends Model
{
    protected $fillable = [
        'reading_date',
        'temperature'
    ];

    protected $casts = [
        'reading_date' => 'datetime',
        'temperature' => 'decimal:2'
    ];
}
