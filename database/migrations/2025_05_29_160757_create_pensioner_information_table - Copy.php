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
        Schema::create('pensioner_information_clones', function (Blueprint $table) {
            $table->id();
            $table->string('pensioner_information_id');
            $table->string('ppo_no', 20);
            $table->string('first_name', 100);
            $table->enum('type_of_pension', ['Regular', 'Family']);
            $table->string('user_id')->nullable();
            $table->string('retired_employee_id')->nullable();
            $table->enum('relation', ['Self', 'Spouse', 'Son', 'Daughter', 'Other']);
            $table->date('dob')->nullable(); // Date of Birth
            $table->date('doj')->nullable(); // Date of Joining
            $table->date('dor')->nullable(); // Date of Retirement
            $table->date('start_date');
            $table->date('end_date')->nullable(); // Pension end date (if applicable)
            $table->enum('status', ['Active', 'Inactive']);
            $table->string('pan_number', 10)->nullable();
            $table->string('pay_level', 50)->nullable();
            $table->string('pay_commission', 50)->nullable();
            $table->text('address');
            $table->string('city', 50);
            $table->string('state', 50);
            $table->string('pin_code', 10);
            $table->string('mobile_no', 15)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('pay_cell')->nullable();
            $table->string('pay_commission_at_retirement')->nullable();
            $table->float('basic_pay_at_retirement', 12, 2)->nullable();
            $table->float('last_drawn_salary', 12, 2)->nullable();
            $table->float('NPA', 10, 2)->nullable();
            $table->float('HRA', 10, 2)->nullable();
            $table->float('special_pay', 10, 2)->nullable();
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
        Schema::dropIfExists('pensioner_information');
    }
};
