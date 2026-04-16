<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_kind', ['receivable', 'payable', 'advance'])
                ->nullable()
                ->after('type');
        });

        DB::table('payments')
            ->whereNotNull('sale_id')
            ->update(['payment_kind' => 'receivable']);

        DB::table('payments')
            ->whereNotNull('purchase_id')
            ->update(['payment_kind' => 'payable']);

        DB::table('payments')
            ->whereNull('sale_id')
            ->whereNull('purchase_id')
            ->update(['payment_kind' => 'advance']);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_kind');
        });
    }
};
