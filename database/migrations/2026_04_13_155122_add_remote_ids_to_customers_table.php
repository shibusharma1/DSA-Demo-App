<?php
// DSA: database/migrations/xxxx_add_remote_ids_to_customers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('zoho_id',       100)->nullable()->after('id');
            $table->string('erpnext_id',    100)->nullable()->after('zoho_id');
            $table->string('tally_id',      100)->nullable()->after('erpnext_id');
            $table->string('quickbooks_id', 100)->nullable()->after('tally_id');
            $table->string('busy_id',       100)->nullable()->after('quickbooks_id');
            $table->string('sap_id',        100)->nullable()->after('busy_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'zoho_id', 'erpnext_id', 'tally_id',
                'quickbooks_id', 'busy_id', 'sap_id',
            ]);
        });
    }
};