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
        Schema::create('pensioner_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pensioner_id')->constrained('pensioner_information');
            $table->enum('document_type',['PAN Card', 'Address Proof','Bank Details','Retirement Order','Life Certificate']);
            $table->string('document_number', 50);
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('file_path', 50);
            $table->timestamp('upload_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pensioner_documents');
    }
};
