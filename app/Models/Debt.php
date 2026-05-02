<?php

namespace App\Models;

use Database\Factories\DebtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['transaction_id', 'farmer_id', 'principal', 'interest_rate', 'total_due', 'amount_repaid', 'balance', 'status'])]
class Debt extends Model
{
    /** @use HasFactory<DebtFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'principal' => 'decimal:2',
            'interest_rate' => 'decimal:4',
            'total_due' => 'decimal:2',
            'amount_repaid' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }
}
