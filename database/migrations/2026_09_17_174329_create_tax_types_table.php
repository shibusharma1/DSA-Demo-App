
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tax_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');

            $table->string('busytax_id', 100)->nullable();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->decimal('percent', 8, 4)->default(0);
            $table->boolean('default_flag')->default(false);
            $table->string('status', 20)->default('Active');
            $table->timestamps();

            $table->unique(
                ['company_id', 'busytax_id'],
                'tax_types_company_busy_unique'
            );
            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_types');
    }
};