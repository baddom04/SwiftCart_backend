<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grocery extends Model
{
    /** @use HasFactory<\Database\Factories\GroceryFactory> */
    use HasFactory;

    public static function getUnitTypes(): array
    {
        return ['pieces', 'pair', 'kilogram', 'pound', 'inch', 'ounce', 'liter', 'decagram', 'deciliter'];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
