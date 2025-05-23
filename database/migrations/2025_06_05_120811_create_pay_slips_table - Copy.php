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
        Schema::create('pay_slip_clones', function (Blueprint $table) {
            $table->id();
            $table->string('pay_slip_id');
            $table->string('net_salary_id');
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
