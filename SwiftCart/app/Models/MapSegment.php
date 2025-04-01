<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\Contracts\HasAbilities;

class MapSegment extends Model
{
    use HasFactory;
    public static function getSegmentTypes(): array
    {
        return ['shelf', 'fridge', 'empty', 'cashregister', 'entrance', 'wall'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
