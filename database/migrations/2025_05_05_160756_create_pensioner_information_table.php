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
        Schema::create('pensioner_information', function (Blueprint $table) {
            $table->id();
            $table->string('ppo_no', 20)->unique();
            $table->string('name', 100);
            $table->enum('type_of_pension', ['Regular', 'Family']);
            $table->foreignId('retired_employee_id')->nullable()->constrained('employees');
            $table->enum('relation', ['Self', 'Spouse', 'Son', 'Daughter', 'Other']);
            $table->date('dob')->nullable(); // Date of Birth
            $table->date('doj')->nullable(); // Date of Joining
            $table->date('dor')->nullable(); // Date of Retirement
            $table->date('end_date')->nullable(); // Pension end date (if applicable)
            $table->enum('status', ['Active', 'Expired', 'Suspended']);
            $table->string('pan_number', 10)->nullable();
            $table->string('pay_level', 50)->nullable();
            $table->string('pay_commission', 50)->nullable();
            $table->string('equivalent_level', 50);
            $table->text('address');
            $table->string('city', 50);
            $table->string('state', 50);
            $table->string('pin_code', 10);
            $table->string('mobile_no', 15)->nullable();
            $table->string('email', 100)->nullable();
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
