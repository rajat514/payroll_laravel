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
        Schema::create('employee_quarter_clones', function (Blueprint $table) {
            $table->id();
            $table->string('employee_quarter_id');
            $table->string('employee_id');
            $table->string('quarter_id');
            $table->date('date_of_allotment');
            $table->date('date_of_occupation');
            $table->date('date_of_leaving')->nullable();
            $table->boolean('is_current')->default(1);
            $table->boolean('is_occupied')->default(0);
            $table->string('order_reference')->nullable();
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
        Schema::dropIfExists('employee_quarters');
    }
};
