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
        Schema::table('pensioner_information', function (Blueprint $table) {
            $table->renameColumn('name', 'first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('pay_cell');
            $table->string('pay_commission_at_retirement');
            $table->float('basic_pay_at_retirement', 12, 2);
            $table->float('last_drawn_salary', 12, 2);
            $table->float('NPA', 10, 2)->nullable();
            $table->float('HRA', 10, 2)->nullable();
            $table->float('special_pay', 10, 2)->nullable();
            $table->enum('status', ['Active', 'Deceased'])->change();
            $table->date('start_date')->nullable();
            $table->dropColumn('equivalent_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pensioner_information', function (Blueprint $table) {
            $table->renameColumn('first_name', 'name');
            $table->dropColumn('middle_name');
            $table->dropColumn('last_name');
            $table->dropColumn('pay_cell');
            $table->dropColumn('pay_commission_at_retirement');
            $table->dropColumn('basic_pay_at_retirement');
            $table->dropColumn('last_drawn_salary');
            $table->dropColumn('NPA');
            $table->dropColumn('HRA');
            $table->dropColumn('special_pay');
            $table->dropColumn('start_date');
            $table->enum('status', ['Active', 'Expired', 'Suspended'])->change();
            $table->string('equivalent_level');
        });
    }
};
