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
        Schema::create('net_pensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pensioner_id')->constrained('pensioner_information');
            $table->foreignId('pensioner_bank_id')->constrained('bank_accounts');
            $table->integer('month');
            $table->integer('year');
            $table->float('net_pension', 12, 2);
            $table->date('processing_date');
            $table->date('payment_date')->nullable();
            $table->boolean('is_verified')->default(0);
            $table->foreignId('verified_by')->nullable()->constrained('users');
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
