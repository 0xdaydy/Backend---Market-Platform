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
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->unique()->constrained('transactions');
            $table->foreignId('farmer_id')->constrained('farmers')->cascadeOnDelete();
            $table->decimal('principal', 12, 2);
            $table->decimal('interest_rate', 5, 4)->default(0);
            $table->decimal('total_due', 12, 2);
            $table->decimal('amount_repaid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2);
            $table->string('status')->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
