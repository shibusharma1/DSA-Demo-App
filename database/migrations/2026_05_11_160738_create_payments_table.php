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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->integer('company_id')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('payment_received', 12, 2)->default(0);
            $table->decimal('due_payment', 12, 2)->default(0);

            $table->string('payment_method')->nullable();
            $table->text('payment_note')->nullable();

            $table->date('payment_date')->nullable();

            $table->string('payment_status')->default('Pending');
            $table->text('payment_status_note')->nullable();

            $table->string('zborder_id')->nullable();

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
        Schema::dropIfExists('payments');
    }
};
