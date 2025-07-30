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
        // Schema::table('users', function ($table) {
        //     $table->dropForeign('users_role_id_foreign');

        //     $table->dropColumn('role_id');
        // });

        Schema::drop('roles');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
