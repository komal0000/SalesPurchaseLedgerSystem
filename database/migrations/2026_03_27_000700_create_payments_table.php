<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('party_id')->constrained('parties');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['received', 'given']);
            $table->foreignUuid('account_id')->constrained('accounts');
            $table->foreignUuid('sale_id')->nullable()->constrained('sales');
            $table->foreignUuid('purchase_id')->nullable()->constrained('purchases');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
