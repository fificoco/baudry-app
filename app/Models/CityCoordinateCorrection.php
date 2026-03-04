<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CityCoordinateCorrection extends Model
{
    protected $fillable = [
        'city_id',
        'old_lat',
        'old_lng',
        'new_lat',
        'new_lng',
        'updated_by',
        'reason',
    ];

    protected $casts = [
        'old_lat' => 'float',
        'old_lng' => 'float',
        'new_lat' => 'float',
        'new_lng' => 'float',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
