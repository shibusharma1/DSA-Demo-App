
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->bigIncrements('id');

            // $table->string('unique_id')->unique();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();

            $table->string('employee_type')->nullable();

            $table->decimal('payment_received', 15, 2)->default(0);
            $table->decimal('due_payment', 15, 2)->default(0);

            $table->string('payment_method')->nullable();
            $table->text('payment_note')->nullable();

            $table->string('status')->default('Pending');

            $table->string('image')->nullable();
            $table->string('image_path')->nullable();

            $table->date('payment_date')->nullable();
            $table->date('next_date')->nullable();

            $table->unsignedBigInteger('bank_id')->nullable();

            $table->string('cheque_no')->nullable();
            $table->date('cheque_date')->nullable();

            $table->string('payment_status')->nullable();
            $table->text('payment_status_note')->nullable();

            $table->unsignedBigInteger('collection_types_id')->nullable();

            $table->boolean('include_in_credit')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // ERP integration identifiers
            $table->string('erpnxtpayment_id')->nullable();
            $table->string('busycollection_id')->nullable();

            // Query and relationship indexes
            $table->index('company_id');
            $table->index('client_id');
            $table->index('order_id');
            $table->index('employee_id');
            $table->index('bank_id');
            $table->index('collection_types_id');

            $table->index('payment_date');
            $table->index('payment_status');

            // BUSY collection IDs are scoped to a company.
            // $table->unique(
            //     ['company_id', 'busycollection_id'],
            //     'collections_company_busy_unique'
            // );

            // ERPNext IDs are also scoped to a company.
            // $table->unique(
            //     ['company_id', 'erpnxtpayment_id'],
            //     'collections_company_erpnext_unique'
            // );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};