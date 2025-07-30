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
        Schema::create('pension_related_info_clones', function (Blueprint $table) {
            $table->id();
            $table->string('pension_rel_info_id');
            $table->string('pensioner_id');
            $table->float('basic_pension', 10, 2);
            $table->float('commutation_amount', 10, 2)->nullable();
            $table->boolean('is_active')->default(1);
            $table->float('additional_pension', 10, 2)->nullable();
            $table->float('medical_allowance', 10, 2)->nullable(); // Medical allowance
            $table->string('arrear_type')->nullable();
            $table->float('total_arrear', 10, 2)->nullable();
            $table->string('arrear_remarks')->nullable();
            $table->string('remarks', 255)->nullable();  //  Remarks (expiry or other notes)
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
        Schema::dropIfExists('pension_related_infos');
    }
};
