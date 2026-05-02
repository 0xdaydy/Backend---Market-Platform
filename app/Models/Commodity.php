<?php

namespace App\Models;

use Database\Factories\CommodityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'unit'])]
class Commodity extends Model
{
    /** @use HasFactory<CommodityFactory> */
    use HasFactory;

    public function rates(): HasMany
    {
        return $this->hasMany(CommodityRate::class);
    }
}
