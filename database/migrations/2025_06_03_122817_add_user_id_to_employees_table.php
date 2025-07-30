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
        Schema::table('employees', function (Blueprint $table) {
            // $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('middle_name')->nullable();
            $table->string('employee_code')->nullable();
            $table->enum('prefix', ['Mr.', 'Mrs.', 'Ms.', 'Dr.'])->nullable();
            $table->enum('institute', ['NIOH', 'ROHC', 'BOTH'])->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('institute', ['NIOH', 'ROHC', 'BOTH'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropColumn('employee_code');
            $table->dropColumn('middle_name');
            $table->dropColumn('prefix');
        });
    }
};
