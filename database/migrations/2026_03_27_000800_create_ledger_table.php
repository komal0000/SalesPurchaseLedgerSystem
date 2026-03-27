<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('party_id')->nullable()->constrained('parties');
            $table->foreignUuid('account_id')->nullable()->constrained('accounts');
            $table->decimal('dr_amount', 15, 2)->default(0);
            $table->decimal('cr_amount', 15, 2)->default(0);
            $table->enum('type', ['sale', 'purchase', 'payment']);
            $table->uuid('ref_id');
            $table->string('ref_table');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger');
    }
};
