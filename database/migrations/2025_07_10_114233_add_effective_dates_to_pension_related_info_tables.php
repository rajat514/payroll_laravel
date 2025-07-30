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
        // Add effective dates to pension_related_infos table
        Schema::table('pension_related_infos', function (Blueprint $table) {
            $table->date('effective_from')->nullable()->after('pensioner_id');
            $table->date('effective_till')->nullable()->after('effective_from');
        });

        // Add effective dates to pension_related_info_clones table
        Schema::table('pension_related_info_clones', function (Blueprint $table) {
            $table->date('effective_from')->nullable()->after('pension_rel_info_id');
            $table->date('effective_till')->nullable()->after('effective_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove effective dates from pension_related_infos table
        Schema::table('pension_related_infos', function (Blueprint $table) {
            $table->dropColumn(['effective_from', 'effective_till']);
        });

        // Remove effective dates from pension_related_info_clones table
        Schema::table('pension_related_info_clones', function (Blueprint $table) {
            $table->dropColumn(['effective_from', 'effective_till']);
        });
    }
};
