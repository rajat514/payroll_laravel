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
        Schema::create('monthly_pensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pension_rel_info_id')->constrained('pension_related_infos');
            $table->foreignId('net_pension_id')->constrained();
            $table->float('basic_pension', 10, 2);
            $table->float('additional_pension', 10, 2)->nullable();
            $table->foreignId('dr_id')->nullable()->constrained('dearness_reliefs'); // References DR rate applied
            $table->float('dr_amount', 10, 2)->nullable(); // Calculated DR amount
            $table->float('medical_allowance', 10, 2)->nullable(); // Medical allowance
            $table->float('total_arrear', 10, 2)->nullable();
            $table->float('total_pension', 10, 2); // 
            $table->string('remarks', 255)->nullable();  //  Remarks (expiry or other notes)
            $table->enum('status', ['Pending', 'Processed', 'Paid'])->default('Pending');
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
        Schema::dropIfExists('monthly_pensions');
    }
};
