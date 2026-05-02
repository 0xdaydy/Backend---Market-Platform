<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('debt_repayment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained('debts')->cascadeOnDelete();
            $table->foreignId('repayment_id')->constrained('repayments')->cascadeOnDelete();
            $table->decimal('amount_applied', 12, 2);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_repayment');
    }
};
