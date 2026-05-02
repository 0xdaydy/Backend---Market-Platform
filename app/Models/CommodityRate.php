<?php

namespace App\Models;

use Database\Factories\CommodityRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['commodity_id', 'rate_fcfa_per_unit', 'is_active'])]
class CommodityRate extends Model
{
    /** @use HasFactory<CommodityRateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'rate_fcfa_per_unit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }
}
