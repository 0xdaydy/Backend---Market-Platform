<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtRepayment extends Model
{
    protected $table = 'debt_repayment';

    public $timestamps = false;

    protected $fillable = ['debt_id', 'repayment_id', 'amount_applied'];

    protected function casts(): array
    {
        return [
            'amount_applied' => 'decimal:2',
        ];
    }
}
