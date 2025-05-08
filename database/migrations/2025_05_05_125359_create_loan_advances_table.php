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
        Schema::create('loan_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained();
            $table->enum('loan_type', ['Computer', 'Housing', 'Vehicle', 'Festival', 'Other']);
            $table->float('loan_amount', 12, 2);
            $table->float('interest_rate', 5, 2);
            $table->date('sanctioned_date');
            $table->integer('total_installments');
            $table->integer('current_installment');
            $table->float('remaining_balance', 12, 2);
            $table->boolean('is_active')->default(1);
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
        Schema::dropIfExists('loan_advances');
    }
};
