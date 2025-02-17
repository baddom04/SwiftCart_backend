<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    /** @use HasFactory<\Database\Factories\HouseholdFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function users()
    {
        return $this->belongsToMany(Household::class, 'user_households', 'household_id', 'user_id');
    }

    public function user_households(): HasMany
    {
        return $this->hasMany(UserHousehold::class);
    }

    public function groceries(): HasMany
    {
        return $this->hasMany(Grocery::class);
    }

    public function household_applications(): HasMany
    {
        return $this->hasMany(HouseholdApplication::class);
    }
}
