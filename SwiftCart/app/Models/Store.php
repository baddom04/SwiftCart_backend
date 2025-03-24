<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Store extends Model
{
    use HasFactory;
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function map() : HasOne
    {
        return $this->hasOne(Map::class);
    }
    public function location() : HasOne 
    {
        return $this->hasOne(Location::class);    
    }
}
