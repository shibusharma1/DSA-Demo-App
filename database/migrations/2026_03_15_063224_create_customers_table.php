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
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); // Primary key auto-increment
            $table->string('contact_name'); // Name of the contact
            $table->string('email')->unique(); // Email, unique
            $table->string('company_name')->nullable(); // Company name, nullable
            $table->string('phone')->nullable(); // Phone number, nullable
            $table->string('zb_id')->nullable()->comment('Zoho Books ID'); // Zoho Books ID
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
