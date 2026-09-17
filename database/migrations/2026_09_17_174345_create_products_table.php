
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');

            $table->string('busyproduct_id', 100)->nullable();
            $table->string('product_name');
            $table->string('product_code')->nullable();

            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('brand')->nullable();

            // The BUSY sync service stores unit_types.id in this column.
            $table->unsignedBigInteger('unit')->nullable();

            $table->decimal('mrp', 14, 4)->nullable();
            $table->text('details')->nullable();
            $table->text('short_desc')->nullable();
            $table->string('status', 20)->default('Active');

            $table->timestamps();

            $table->unique(
                ['company_id', 'busyproduct_id'],
                'products_company_busy_unique'
            );
            $table->index(['company_id', 'product_name']);
            $table->index(['company_id', 'category_id']);
            $table->index(['company_id', 'unit']);
            $table->index(['company_id', 'status']);

            // Foreign keys are added below, after the referenced tables exist.
            $table->foreign('category_id')
                ->references('id')
                ->on('item_categories')
                ->nullOnDelete();

            $table->foreign('unit')
                ->references('id')
                ->on('unit_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};