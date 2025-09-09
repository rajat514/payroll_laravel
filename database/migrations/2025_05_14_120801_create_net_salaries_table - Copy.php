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
        Schema::create('net_salary_clones', function (Blueprint $table) {
            $table->id();
            $table->string('net_salary_id');
            $table->string('employee_id');
            $table->integer('month');
            $table->integer('year');
            $table->date('processing_date');
            $table->float('net_amount', 12, 2);
            $table->date('payment_date');
            $table->string('employee_bank_id');
            $table->boolean('is_verified')->default(0);
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->boolean('salary_processing_status')->default(0);
            $table->timestamp('salary_processing_date')->nullable();
            $table->boolean('ddo_status')->default(0);
            $table->timestamp('ddo_date')->nullable();
            $table->boolean('section_officer_status')->default(0);
            $table->timestamp('section_officer_date')->nullable();
            $table->boolean('account_officer_status')->default(0);
            $table->timestamp('account_officer_date')->nullable();
            $table->string('remarks')->nullable();
            $table->string('is_finalize')->nullable();
            $table->text('employee')->nullable();
            $table->text('employee_bank')->nullable();
            $table->timestamp('released_date')->nullable();
            $table->timestamp('finalized_date')->nullable();

            $table->string('pay_structure_id');
            $table->float('basic_pay', 12, 2);
            $table->string('da_rate_id')->nullable();
            $table->float('da_amount', 10, 2)->nullable();
            $table->string('hra_rate_id')->nullable();
            $table->float('hra_amount', 10, 2)->nullable();
            $table->string('npa_rate_id')->nullable();
            $table->float('npa_amount', 10, 2)->nullable();
            $table->string('transport_rate_id')->nullable();
            $table->float('transport_amount', 10, 2)->nullable();
            $table->string('uniform_rate_id')->nullable();
            $table->float('uniform_rate_amount', 10, 2)->nullable();
            $table->float('pay_plus_npa', 12, 2)->nullable();
            $table->float('govt_contribution', 10, 2)->nullable();
            $table->float('da_on_ta', 10, 2)->nullable();
            $table->float('arrears', 10, 2)->nullable();
            $table->float('spacial_pay', 10, 2)->nullable();
            $table->float('da_1', 10, 2)->nullable();
            $table->float('da_2', 10, 2)->nullable();
            $table->float('itc_leave_salary', 10, 2)->nullable();
            $table->float('total_pay', 12, 2);
            $table->text('salary_arrears')->nullable();

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
            $table->text('deduction_recoveries')->nullable();

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
        Schema::dropIfExists('net_salaries');
    }
};
