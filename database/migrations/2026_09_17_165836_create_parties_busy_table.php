
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties_busy', function (Blueprint $table) {
            $table->bigIncrements('id');

            // BUSY integration identifiers
            $table->unsignedBigInteger('company_id');
            $table->string('busyparty_id', 100);

            // Party information
            $table->string('company_name');
            $table->string('name')->nullable();

            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('email')->nullable();

            $table->text('address_1')->nullable();
            $table->text('address_2')->nullable();

            // References to your existing country table
            $table->unsignedBigInteger('country')->nullable();
            $table->string('phonecode', 10)->nullable();

            $table->string('pan', 100)->nullable();

            // Financial information
            $table->unsignedInteger('credit_days')->nullable();
            $table->decimal('opening_balance', 15, 2)->nullable();
            $table->decimal('closing_balance', 15, 2)->nullable();

            $table->string('status', 30)->default('Active');

            $table->timestamps();

            // Prevent duplicate BUSY parties within a company.
            $table->unique(
                ['company_id', 'busyparty_id'],
                'parties_busy_company_party_unique'
            );

            $table->index('company_id');
            $table->index('country');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties_busy');
    }
};