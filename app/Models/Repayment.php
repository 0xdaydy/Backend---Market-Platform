<?php

namespace App\Models;

use Database\Factories\RepaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['farmer_id', 'operator_id', 'amount', 'payment_method', 'reference'])]
class Repayment extends Model
{
    /** @use HasFactory<RepaymentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function debts(): BelongsToMany
    {
        return $this->belongsToMany(Debt::class, 'debt_repayment')
            ->withPivot('amount_applied', 'created_at');
    }
}
