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
        Schema::create('net_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained();
            $table->integer('month');
            $table->integer('year');
            $table->date('processing_date');
            $table->float('net_amount', 12, 2);
            $table->date('payment_date')->nullable();
            $table->foreignId('employee_bank_id')->constrained('employee_bank_accounts');
            $table->boolean('is_verified')->default(0);
            $table->foreignId('verified_by')->nullable()->constrained('users');
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
