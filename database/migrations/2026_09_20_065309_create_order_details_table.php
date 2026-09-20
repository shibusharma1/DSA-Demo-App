<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_details', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Parent Order
            |--------------------------------------------------------------------------
            */
            $table->unsignedBigInteger('order_id')
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Product
            |--------------------------------------------------------------------------
            |
            | References products.id.
            | BUSY product code is available through
            | products.busyproduct_id.
            |
            */
            $table->unsignedBigInteger('product_id')
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Unit
            |--------------------------------------------------------------------------
            |
            | References unit_types.id.
            | BUSY unit code is available through
            | unit_types.busyunit_id.
            |
            */
            $table->unsignedBigInteger('unit_id')
                ->nullable()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Tax
            |--------------------------------------------------------------------------
            |
            | References tax_types.id.
            | BUSY tax code is available through
            | tax_types.busytax_id.
            |
            */
            $table->unsignedBigInteger('tax_id')
                ->nullable()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Pricing
            |--------------------------------------------------------------------------
            */
            $table->decimal('rate', 15, 4)
                ->default(0);

            $table->decimal('quantity', 15, 4)
                ->default(1);

            /*
            |--------------------------------------------------------------------------
            | Discount
            |--------------------------------------------------------------------------
            */
            $table->decimal('discount', 15, 4)
                ->default(0);

            $table->string('discount_type')
                ->default('percent');

            $table->decimal('discount_amount', 15, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Applied Rate
            |--------------------------------------------------------------------------
            */
            $table->decimal('applied_rate', 15, 4)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Tax Calculation
            |--------------------------------------------------------------------------
            |
            | tax_id points to tax_types.
            | tax_rate is kept as the calculated rate used for this
            | particular order line.
            |
            */
            $table->decimal('tax_rate', 8, 3)
                ->default(0);

            $table->decimal('tax_amount', 15, 2)
                ->default(0);

            $table->decimal('taxable_amount', 15, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Final Line Amount
            |--------------------------------------------------------------------------
            */
            $table->decimal('amount', 15, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Optional Line Description
            |--------------------------------------------------------------------------
            */
            $table->text('description')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Display Ordering
            |--------------------------------------------------------------------------
            */
            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();

            $table->foreign('unit_id')
                ->references('id')
                ->on('unit_types')
                ->nullOnDelete();

            $table->foreign('tax_id')
                ->references('id')
                ->on('tax_types')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Useful Index
            |--------------------------------------------------------------------------
            */
            $table->index([
                'order_id',
                'product_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};