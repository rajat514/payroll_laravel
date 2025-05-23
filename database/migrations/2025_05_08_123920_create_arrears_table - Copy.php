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
        Schema::create('arrear_clones', function (Blueprint $table) {
            $table->id();
            $table->string('arrears_id');
            $table->string('pensioner_id');
            $table->date('from_month');
            $table->date('to_month');
            $table->date('payment_month');
            $table->float('basic_arrear', 10, 2);
            $table->float('additional_arrear', 10, 2);
            $table->float('dr_percentage', 5, 2);
            $table->float('dr_arrear', 10, 2);
            $table->float('total_arrear', 10, 2);
            $table->string('remarks', 255);
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
        Schema::dropIfExists('arrears');
    }
};
