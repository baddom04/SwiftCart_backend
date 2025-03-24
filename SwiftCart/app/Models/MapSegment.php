<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\Contracts\HasAbilities;

class MapSegment extends Model
{
    public static function getSegmentTypes(): array
    {
        return ['shelf', 'fridge', 'empty', 'outside', 'cash_register', 'entrance', 'wall'];
    }

    public function section() : BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }
    public function products() : HasMany
    {
        return $this->hasMany(Product::class);
    }
}
