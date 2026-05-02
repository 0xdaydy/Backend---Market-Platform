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
        Schema::create('farmers', function (Blueprint $table) {
            $table->id();
            $table->string('card_id')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('village')->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('credit_balance_fcfa', 12, 2)->default(0);
            $table->timestamps();

            $table->index('card_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farmers');
    }
};
