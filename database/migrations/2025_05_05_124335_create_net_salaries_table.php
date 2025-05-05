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
        Schema::create('net_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pay_slip_id')->constrained();
            $table->foreignId('deduction_id')->constrained();
            $table->float('net_amount', 12, 2);
            $table->date('payment_date');
            $table->foreignId('employee_bank_id')->constrained('employee_bank_accounts');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('net_salaries');
    }
};
