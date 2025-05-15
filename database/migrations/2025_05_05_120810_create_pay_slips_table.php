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
        Schema::create('pay_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('net_salary_id')->constrained('net_salaries');
            $table->foreignId('pay_structure_id')->constrained('employee_pay_structures');
            $table->float('basic_pay', 12, 2);
            $table->foreignId('da_rate_id')->nullable()->constrained('dearnes_allowance_rates');
            $table->float('da_amount', 10, 2)->default(0.0);
            $table->foreignId('hra_rate_id')->nullable()->constrained('house_rent_allowance_rates');
            $table->float('hra_amount', 10, 2)->default(0.0);
            $table->foreignId('npa_rate_id')->nullable()->constrained('non_practicing_allowance_rates');
            $table->float('npa_amount', 10, 2)->default(0.0);
            $table->foreignId('transport_rate_id')->nullable()->constrained('employee_transport_allowances');
            $table->float('transport_amount', 10, 2)->default(0.0);
            $table->foreignId('uniform_rate_id')->nullable()->constrained('uniform_allowance_rates');
            $table->float('uniform_rate_amount', 10, 2)->default(0.0);
            $table->float('pay_plus_npa', 12, 2)->default(0.0);
            $table->float('govt_contribution', 10, 2)->default(0.0);
            $table->float('da_on_ta', 10, 2)->default(0.0);
            $table->float('arrears', 10, 2)->default(0.0);
            $table->float('spacial_pay', 10, 2)->default(0.0);
            $table->float('da_1', 10, 2)->default(0.0);
            $table->float('da_2', 10, 2)->default(0.0);
            $table->float('itc_leave_salary', 10, 2)->default(0.0);
            $table->float('total_pay', 12, 2);
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
        Schema::dropIfExists('pay_slips');
    }
};
