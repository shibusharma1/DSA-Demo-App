<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->nullable();

            $table->string('product_name');
            $table->string('product_code')->nullable();

            $table->decimal('mrp', 10, 2)->nullable();
            $table->decimal('d_price', 10, 2)->nullable();
            $table->decimal('r_price', 10, 2)->nullable();

            $table->string('unit_name')->nullable();

            $table->integer('inventory_available_quantity')->default(0);

            $table->string('status')->default('Active');

            // $table->string('zbproduct_id')->nullable(); // external id
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

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
