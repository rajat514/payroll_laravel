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
        Schema::create('deduction_clones', function (Blueprint $table) {
            $table->id();
            $table->string('deduction_id');
            $table->string('net_salary_id');
            $table->string('net_salary_clone_id')->nullable();
            $table->float('income_tax', 10, 2)->nullable();
            $table->float('professional_tax', 10, 2)->nullable();
            $table->float('license_fee', 10, 2)->nullable();
            $table->float('nfch_donation', 10, 2)->nullable();
            $table->float('gpf', 10, 2)->nullable();
            $table->float('transport_allowance_recovery', 10, 2)->nullable();
            $table->float('hra_recovery', 10, 2)->nullable();
            $table->float('computer_advance', 10, 2)->nullable();
            $table->float('computer_advance_installment', 10, 2)->nullable();
            $table->integer('computer_advance_inst_no')->nullable();
            $table->float('computer_advance_balance', 10, 2)->nullable();
            $table->float('employee_contribution_10', 10, 2)->nullable();
            $table->float('govt_contribution_14_recovery', 10, 2)->nullable();
            $table->float('dies_non_recovery', 10, 2)->nullable();
            $table->float('computer_advance_interest', 10, 2)->nullable();
            $table->float('gis', 10, 2)->nullable();
            $table->float('pay_recovery', 10, 2)->nullable();
            $table->float('nps_recovery', 10, 2)->nullable();
            $table->float('lic', 10, 2)->nullable();
            $table->float('credit_society', 10, 2)->nullable();
            $table->float('total_deductions', 10, 2)->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users');
            $table->foreignId('edited_by')->nullable()->constrained('users');
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
