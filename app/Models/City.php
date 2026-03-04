<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = [
        'name',
        'postal_code',
        'lat',
        'lng',
        'is_active',
    ];

    protected $casts = [
        'lat'       => 'float',
        'lng'       => 'float',
        'is_active' => 'boolean',
    ];

    public function coordinateCorrections(): HasMany
    {
        return $this->hasMany(CityCoordinateCorrection::class)->latest();
    }
}
