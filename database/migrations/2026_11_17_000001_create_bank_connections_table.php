<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_connections', function (Blueprint $table) {
            $table->id();
            $table->string('pluggy_item_id')->unique();
            $table->string('connector_name');
            $table->string('connector_logo_url')->nullable();
            $table->string('status')->default('pending');
            $table->text('last_error')->nullable();
            $table->boolean('sync_enabled')->default(true);
            $table->timestamp('consent_expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_connections');
    }
};
