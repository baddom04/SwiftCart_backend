<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory;
    public function map() : BelongsTo
    {
        return $this->belongsTo(Map::class);
    }
    public function map_segments() : HasMany
    {
        return $this->hasMany(MapSegment::class);
    }
}
