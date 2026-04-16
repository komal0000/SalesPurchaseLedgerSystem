<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties');
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('advance_date');
            $table->string('advance_date_bs', 10);
            $table->string('salary_month', 7);
            $table->string('remarks', 1000)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'salary_month']);
            $table->index(['salary_month']);
            $table->index(['advance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_advances');
    }
};
