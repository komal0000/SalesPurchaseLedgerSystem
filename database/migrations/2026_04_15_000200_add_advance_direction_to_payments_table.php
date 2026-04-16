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
            $table->enum('advance_direction', ['paid', 'received'])
                ->nullable()
                ->after('payment_kind');
        });

        DB::table('payments')
            ->where('payment_kind', 'advance')
            ->where('type', 'given')
            ->update(['advance_direction' => 'paid']);

        DB::table('payments')
            ->where('payment_kind', 'advance')
            ->where('type', 'received')
            ->update(['advance_direction' => 'received']);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('advance_direction');
        });
    }
};
