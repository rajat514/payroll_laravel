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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_retired')->default(0);
        });

        Schema::table('user_clones', function (Blueprint $table) {
            $table->boolean('is_retired')->default(0);
        });

        Schema::table('pensioner_information', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_retired');
        });

        Schema::table('user_clones', function (Blueprint $table) {
            $table->dropColumn('is_retired');
        });

        Schema::table('pensioner_information', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
