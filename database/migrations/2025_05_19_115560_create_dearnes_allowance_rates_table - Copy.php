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
        Schema::create('dearnes_allowance_rate_clones', function (Blueprint $table) {
            $table->id();
            $table->string('dearnes_allowance_rate_id');
            $table->float('rate_percentage', 6, 2);
            $table->float('pwd_rate_percentage', 6, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_till')->nullable();
            $table->string('notification_ref', 50)->nullable();
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
        Schema::dropIfExists('dearnes_allowance_rates');
    }
};
