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
        Schema::create('pension_deduction_clones', function (Blueprint $table) {
            $table->id();
            $table->string('pension_deduction_id');
            $table->string('net_pension_id');
            $table->string('net_pension_clone_id')->nullable();
            $table->float('commutation_amount', 10, 2)->nullable();
            $table->float('income_tax', 10, 2)->nullable();
            $table->float('recovery', 10, 2)->nullable();
            $table->float('other', 10, 2)->nullable();
            $table->float('amount', 10, 2);
            $table->string('description', 255)->nullable();
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
        Schema::dropIfExists('pension_deductions');
    }
};
