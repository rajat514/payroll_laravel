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
        Schema::table('net_pensions', function (Blueprint $table) {
            $table->text('pensioner')->nullable();
            $table->text('pensioner_bank')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('net_pensions', function (Blueprint $table) {
            $table->dropColumn([
                'pensioner',
                'pensioner_bank'
            ]);
        });
    }
};
