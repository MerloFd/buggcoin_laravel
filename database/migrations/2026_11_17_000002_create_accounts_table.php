<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            // Null = manual account. Restrict: deleting a connection never deletes data.
            $table->foreignId('bank_connection_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('pluggy_account_id')->nullable()->unique();
            $table->string('type');
            $table->string('name');
            // Signed on purpose: a balance is a position and may be negative.
            $table->bigInteger('balance_cents')->default(0);
            $table->unsignedBigInteger('credit_limit_cents')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
