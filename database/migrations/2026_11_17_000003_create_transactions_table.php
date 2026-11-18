<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Only the columns bank-sync-and-list uses. Later changes add their own
     * (category_id, is_transfer, merged_into_id, bill_id, installments).
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->string('source');
            // Unique: makes the sync idempotent. Null for manual entries.
            $table->string('pluggy_transaction_id')->nullable()->unique();
            $table->string('direction');
            // Always positive; the sign lives in `direction`.
            $table->unsignedBigInteger('amount_cents');
            $table->date('occurred_on');
            $table->string('description');
            $table->string('pluggy_category')->nullable();
            $table->string('status')->default('posted');
            $table->timestamps();

            $table->index('occurred_on');
            $table->index(['account_id', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
