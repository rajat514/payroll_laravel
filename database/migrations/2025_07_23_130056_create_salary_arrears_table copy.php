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
        Schema::create('salary_arrear_clones', function (Blueprint $table) {
            $table->id();
            $table->string('salary_arrear_id')->nullable();
            $table->string('net_salary_clone_id')->nullable();
            $table->string('pay_slip_clone_id')->nullable();
            $table->string('pay_slip_id');
            $table->string('type');
            $table->float('amount', 10, 2);
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
        Schema::dropIfExists('salary_arrear_clones');
    }
};
