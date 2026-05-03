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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            $table->integer('company_id')->nullable();
            $table->string('client_code')->nullable();

            $table->string('name');
            $table->string('company_name')->nullable();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();

            $table->text('address_1')->nullable();
            $table->text('address_2')->nullable();
            $table->string('location')->nullable();

            $table->string('pan')->nullable();
            $table->string('website')->nullable();

            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('closing_balance', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);

            $table->decimal('credit_limit', 12, 2)->nullable();
            $table->integer('credit_days')->nullable();

            $table->string('status')->default('Active');

            // $table->string('zbcustomer_id')->nullable();
            $table->string('zoho_id',       100)->nullable();
            $table->string('erpnext_id',    100)->nullable();
            $table->string('tally_id',      100)->nullable();
            $table->string('quickbooks_id', 100)->nullable();
            $table->string('busy_id',       100)->nullable();
            $table->string('sap_id',        100)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
