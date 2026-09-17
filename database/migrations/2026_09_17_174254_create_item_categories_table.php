
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');

            $table->string('busyitemcategory_id', 100)->nullable();
            $table->string('name');

            $table->string('status', 20)->default('Active');
            $table->timestamps();

            $table->unique(
                ['company_id', 'busyitemcategory_id'],
                'item_categories_company_busy_unique'
            );
            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_categories');
    }
};