<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The payment-gateway-level record backing either a Purchase (one-time)
     * or a Subscription (recurring) — the ledger the Accounts role
     * reconciles against. gateway/gateway_reference are nullable: real
     * payment gateway integration is Phase 2, this is the schema for it.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('payable');
            $table->decimal('amount', 8, 2);
            $table->string('currency', 3)->default('usd');
            $table->string('gateway')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
