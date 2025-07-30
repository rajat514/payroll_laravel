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
        Schema::create('net_pension_clones', function (Blueprint $table) {
            $table->id();
            $table->string('net_pension_id');
            $table->string('pensioner_id');
            $table->string('pensioner_bank_id');
            $table->integer('month');
            $table->integer('year');
            $table->float('net_pension', 12, 2);
            $table->date('processing_date');
            $table->date('payment_date')->nullable();
            $table->boolean('is_verified')->default(0);
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->boolean('pensioner_operator_status')->default(0);
            $table->timestamp('pensioner_operator_date')->nullable();
            $table->boolean('ddo_status')->default(0);
            $table->timestamp('ddo_date')->nullable();
            $table->boolean('section_officer_status')->default(0);
            $table->timestamp('section_officer_date')->nullable();
            $table->boolean('account_officer_status')->default(0);
            $table->timestamp('account_officer_date')->nullable();

            $table->foreignId('pension_rel_info_id')->constrained('pension_related_infos');
            $table->float('basic_pension', 10, 2);
            $table->float('additional_pension', 10, 2)->nullable();
            $table->foreignId('dr_id')->nullable()->constrained('dearness_reliefs'); // References DR rate applied
            $table->float('dr_amount', 10, 2)->nullable(); // Calculated DR amount
            $table->float('medical_allowance', 10, 2)->nullable(); // Medical allowance
            $table->float('total_arrear', 10, 2)->nullable();
            $table->float('total_pension', 10, 2); // 
            $table->string('remarks', 255)->nullable();  //  Remarks (expiry or other notes)
            $table->enum('status', ['Initiated', 'Approved', 'Disbursed'])->default('Initiated');

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
        Schema::dropIfExists('net_pensions');
    }
};
