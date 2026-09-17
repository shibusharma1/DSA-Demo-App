
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('unit_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');

            $table->string('busyunit_id', 100)->nullable();
            $table->string('name');
            $table->string('symbol', 50)->nullable();
            $table->string('status', 20)->default('Active');
            $table->timestamps();

            $table->unique(
                ['company_id', 'busyunit_id'],
                'unit_types_company_busy_unique'
            );
            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_types');
    }
};