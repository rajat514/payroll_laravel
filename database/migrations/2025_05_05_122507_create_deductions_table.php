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
        Schema::create('deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('pay_slip_id')->constrained();
            $table->float('income_tax', 10, 2);
            $table->float('professional_tax', 10, 2);
            $table->float('license_fee', 10, 2);
            $table->float('nfch_donation', 10, 2);
            $table->float('gpf', 10, 2);
            $table->float('transport_allowance_recovery', 10, 2);
            $table->float('hra_recovery', 10, 2);
            $table->float('computer_advance', 10, 2);
            $table->float('computer_advance_installment', 10, 2);
            $table->integer('computer_advance_inst_no');
            $table->float('computer_advance_balance', 10, 2);
            $table->float('employee_contribution_10', 10, 2);
            $table->float('govt_contribution_14_recovery', 10, 2);
            $table->float('dies_non_recovery', 10, 2);
            $table->float('computer_advance_interest', 10, 2);
            $table->float('gis', 10, 2);
            $table->float('pay_recovery', 10, 2);
            $table->float('nps_recovery', 10, 2);
            $table->float('lic', 10, 2);
            $table->float('credit_society', 10, 2);
            $table->float('total_deductions', 10, 2);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deductions');
    }
};
