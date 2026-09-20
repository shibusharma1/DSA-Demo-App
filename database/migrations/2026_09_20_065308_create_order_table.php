<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Company
            |--------------------------------------------------------------------------
            */
            $table->unsignedBigInteger('company_id')
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Customer / Party
            |--------------------------------------------------------------------------
            |
            | References DSA clients table.
            | BUSY mapping is obtained through clients.busyparty_id.
            |
            */
            $table->unsignedBigInteger('client_id')
                ->index();

            /*
            |--------------------------------------------------------------------------
            | BUSY Integration
            |--------------------------------------------------------------------------
            |
            | BUSY voucher/order identifier.
            |
            */
            $table->string('busyorder_id')
                ->nullable()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Order Information
            |--------------------------------------------------------------------------
            */
            $table->string('order_no')
                ->nullable()
                ->index();

            $table->date('order_date');

            /*
            |--------------------------------------------------------------------------
            | Order To
            |--------------------------------------------------------------------------
            |
            | Kept nullable until the existing Order To table/model
            | is confirmed.
            |
            */
            $table->unsignedBigInteger('order_to_id')
                ->nullable()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */
            $table->longText('order_notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Amounts
            |--------------------------------------------------------------------------
            */
            $table->decimal('sub_total', 15, 2)
                ->default(0);

            $table->decimal('discount', 15, 2)
                ->default(0);

            $table->decimal('total_tax', 15, 2)
                ->default(0);

            $table->decimal('delivery_charge', 15, 2)
                ->default(0);

            $table->decimal('grand_total', 15, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */
            $table->string('status')
                ->default('Pending')
                ->index();

            /*
            |--------------------------------------------------------------------------
            | BUSY Synchronization Status
            |--------------------------------------------------------------------------
            */
            $table->string('busy_sync_status')
                ->default('Pending')
                ->index();

            $table->text('busy_sync_message')
                ->nullable();

            $table->timestamp('busy_synced_at')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Key
            |--------------------------------------------------------------------------
            */
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Useful Composite Index
            |--------------------------------------------------------------------------
            */
            $table->index([
                'company_id',
                'client_id',
                'order_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};