<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Map extends Model
{
    public function store() : BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
    public function sections() : HasMany
    {
        return $this->hasMany(Section::class);
    }
    public function map_segments(): HasMany
    {
        return $this->hasMany(MapSegment::class);
    }
}
