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
        Schema::create('pension_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pension_id')->constrained('monthly_pensions');
            $table->enum('deduction_type', ['Income Tax', 'Recovery', 'Other']);
            $table->float('amount', 10, 2);
            $table->string('description', 255);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pension_deductions');
    }
};
