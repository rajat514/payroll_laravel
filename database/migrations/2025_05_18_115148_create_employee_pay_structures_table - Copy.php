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
        Schema::create('employee_pay_structure_clones', function (Blueprint $table) {
            $table->id();
            $table->string('employee_pay_structure_id');
            $table->string('employee_id');
            $table->string('matrix_cell_id');
            $table->float('commission', 10, 2);
            $table->date('effective_from');
            $table->date('effective_till')->nullable();
            $table->string('order_reference', 50)->nullable();
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
        Schema::dropIfExists('employee_pay_structures');
    }
};
