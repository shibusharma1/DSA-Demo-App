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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->integer('company_id')->nullable();
            $table->integer('client_id')->nullable();

            $table->string('order_no')->nullable();

            $table->decimal('tot_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);

            $table->text('order_note')->nullable();

            $table->date('order_date')->nullable();
            $table->date('due_date')->nullable();

            $table->string('delivery_status')->default('Pending');

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
        Schema::dropIfExists('orders');
    }
};
