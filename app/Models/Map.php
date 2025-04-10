<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Map extends Model
{
    use HasFactory;
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
    public function map_segments(): HasMany
    {
        return $this->hasMany(MapSegment::class);
    }
    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            MapSegment::class,
            'map_id',
            'map_segment_id'
        );
    }
}
