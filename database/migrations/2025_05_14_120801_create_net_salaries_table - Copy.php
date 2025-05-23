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
            $table->foreignId('varified_by')->constrained('users');
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
