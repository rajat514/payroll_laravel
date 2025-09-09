<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make password nullable to allow creating retired users without a password
        DB::statement("ALTER TABLE users MODIFY password VARCHAR(255) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert password back to NOT NULL
        DB::statement("ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL");
    }
};


