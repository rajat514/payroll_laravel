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
        Schema::create('n_p_s_govt_contribution_clone', function (Blueprint $table) {
            $table->id();
            $table->string('n_p_s_govt_contribution_id');
            $table->integer('rate_percentage');
            $table->enum('type', ['Employee', 'GOVT']);
            $table->date('effective_from');
            $table->date('effective_till')->nullable();
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
        Schema::dropIfExists('n_p_s_govt_contributions');
    }
};
