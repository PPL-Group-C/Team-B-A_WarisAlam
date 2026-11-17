<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HeritageSite extends Model
{
    protected $guarded = [];

    public function reports(): HasMany
    {
        return $this->hasMany(DamageReport::class);
    }
}
