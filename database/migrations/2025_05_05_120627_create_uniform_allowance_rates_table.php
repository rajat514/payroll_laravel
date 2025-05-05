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
        Schema::create('uniform_allowance_rates', function (Blueprint $table) {
            $table->id();
            $table->string('applicable_post');
            $table->float('amount', 10, 2);
            $table->date('effective_from');
            $table->date('effective_till')->nullable();
            $table->string('notification_ref', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uniform_allowance_rates');
    }
};
