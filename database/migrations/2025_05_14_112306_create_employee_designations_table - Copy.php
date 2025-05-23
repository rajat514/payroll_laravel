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
        Schema::create('employee_designation_clones', function (Blueprint $table) {
            $table->id();
            $table->string('employee_designation_id');
            $table->string('employee_id');
            $table->string('designation');
            $table->string('cadre');
            $table->enum('job_group', ['A', 'B', 'C', 'D']);
            $table->date('effective_from');
            $table->date('effective_till')->nullable();
            $table->string('promotion_order_no')->nullable();
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
        Schema::dropIfExists('employee_designations');
    }
};
