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
        $tables = [
            'monthly_pensions', 'pension_deductions', 'arrears', 'pensioner_documents'
        ];
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('added_by')->nullable()->constrained('users');
                $table->foreignId('edited_by')->nullable()->constrained('users');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'monthly_pensions', 'pension_deductions', 'arrears', 'pensioner_documents'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign([$table->getTable() . '_added_by_foreign']);
                $table->dropForeign([$table->getTable() . '_edited_by_foreign']);
                $table->dropColumn(['added_by', 'edited_by']);
            });
        }
    }
};
