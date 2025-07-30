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
        Schema::create('employee_clones', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('date_of_birth')->nullable();
            $table->date('date_of_joining')->nullable();
            $table->date('date_of_retirement')->nullable();
            $table->boolean('pwd_status')->default(0);
            $table->enum('pension_scheme', ["GPF", "NPS"])->nullable();
            $table->string('pension_number')->nullable();
            $table->boolean('gis_eligibility')->default(0);
            $table->string('gis_no')->nullable();
            $table->boolean('credit_society_member')->default(0);
            $table->string('email')->nullable();
            $table->string('pancard')->nullable();
            $table->string('increment_month')->nullable();
            $table->boolean('uniform_allowance_eligibility')->default(0);
            $table->boolean('hra_eligibility')->default(0);
            $table->boolean('npa_eligibility')->default(0);
            $table->string('user_id');
            $table->string('middle_name')->nullable();
            $table->string('employee_code')->nullable();
            $table->enum('prefix', ['Mr.', 'Mrs.', 'Ms.', 'Dr.'])->nullable();
            $table->enum('institute', ['NIOH', 'ROHC', 'BOTH'])->nullable();
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
        Schema::dropIfExists('employees');
    }
};
