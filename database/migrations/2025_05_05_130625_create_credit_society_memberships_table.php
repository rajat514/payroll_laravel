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
        Schema::create('credit_society_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained();
            $table->string('society_name');
            $table->string('membership_number');
            $table->date('joining_date');
            $table->date('relieving_date')->nullable();
            $table->float('monthly_subscription', 10, 2);
            $table->float('entrance_fee', 10, 2);
            $table->boolean('is_active')->default(1);
            $table->date('effective_from');
            $table->date('effective_till')->nullable();
            $table->string('remark', 255)->nullable();
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
        Schema::dropIfExists('credit_society_memberships');
    }
};
