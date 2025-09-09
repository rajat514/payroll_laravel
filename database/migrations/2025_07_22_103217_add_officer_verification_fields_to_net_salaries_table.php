<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('net_salaries', function (Blueprint $table) {
            // Salary Processing Officer
            $table->boolean('salary_processing_status')->default(0);
            $table->timestamp('salary_processing_date')->nullable();

            // Drawing and Disbursing Officer (DDO)
            $table->boolean('ddo_status')->default(0);
            $table->timestamp('ddo_date')->nullable();

            // Section Officer
            $table->boolean('section_officer_status')->default(0);
            $table->timestamp('section_officer_date')->nullable();

            // Account Officer
            $table->boolean('account_officer_status')->default(0);
            $table->timestamp('account_officer_date')->nullable();

            $table->string('is_finalize')->default(0);

            $table->string('remarks')->nullable();

            $table->timestamp('released_date')->nullable();
            $table->timestamp('finalized_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('net_salaries', function (Blueprint $table) {
            $table->dropColumn([
                'salary_processing_status',
                'salary_processing_date',
                'ddo_status',
                'ddo_date',
                'section_officer_status',
                'section_officer_date',
                'account_officer_status',
                'account_officer_date',
                'remarks',
                'is_finalize'
            ]);
        });
    }
};
