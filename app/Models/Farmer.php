<?php

namespace App\Models;

use Database\Factories\FarmerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['card_id', 'name', 'phone', 'village', 'credit_limit', 'credit_balance_fcfa'])]
class Farmer extends Model
{
    /** @use HasFactory<FarmerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'credit_balance_fcfa' => 'decimal:2',
        ];
    }
}
