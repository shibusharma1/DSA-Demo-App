<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties_busy', function (Blueprint $table) {
            $table->id();

            /* DSA / Tenant*/
            $table->unsignedBigInteger('company_id');

            /* BUSY Master */
            $table->string('busyparty_id', 100)->nullable();

            $table->string('company_name')->nullable();
            $table->string('name')->nullable();
            $table->string('alias')->nullable();
            $table->string('print_name')->nullable();
            $table->string('parent_group')->nullable();

            $table->boolean('bill_by_bill_balancing')->default(false);

            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('whatsapp_no')->nullable();
            $table->string('fax')->nullable();
            $table->string('email')->nullable();
            $table->string('contact')->nullable();
            $table->string('cont_dept_name')->nullable();

            $table->text('address_1')->nullable();
            $table->text('address_2')->nullable();
            $table->text('address_3')->nullable();
            $table->text('address_4')->nullable();

            $table->unsignedBigInteger('country')->nullable();
            $table->string('phonecode', 20)->nullable();

            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('area')->nullable();

            $table->string('pan')->nullable();
            $table->string('gst_no')->nullable();
            $table->string('tin_no')->nullable();
            $table->string('it_ward')->nullable();
            $table->string('st37')->nullable();

            $table->string('account_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('swift_code')->nullable();

            $table->string('c3')->nullable();
            $table->string('c4')->nullable();
            $table->string('c5')->nullable();
            $table->string('c8')->nullable();

            $table->string('tmp_master_code')->nullable();
            $table->string('tmp_code')->nullable();
            $table->string('tmp_parent_group_code')->nullable();

            $table->string('supplier_type')->nullable();
            $table->integer('credit_days_sale')->nullable();
            $table->integer('credit_days_purchase')->nullable();

            $table->string('price_level')->nullable();
            $table->string('price_level_purchase')->nullable();

            $table->string('tax_type')->nullable();
            $table->string('type_of_dealer_gst')->nullable();

            $table->string('cheque_print_name')->nullable();
            $table->string('reverse_charge_type')->nullable();
            $table->string('input_type')->nullable();

            $table->decimal('opening_balance', 18, 4)->nullable();
            $table->decimal('closing_balance', 18, 4)->nullable();

            $table->string('status', 20)->default('Active');

            $table->string('busy_sync_status', 20)->default('Pending');

            $table->text('busy_sync_message')->nullable();

            $table->timestamp('busy_synced_at')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties_busy');
    }
};