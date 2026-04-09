<?php
// database/migrations/xxxx_create_integration_accounts_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('integration_accounts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->string('provider', 100)->index();
            $table->string('service_type', 100)->nullable()->index();

            $table->string('organization_id', 255)->nullable()->index();
            $table->string('account_id', 255)->nullable()->index();

            $table->string('client_id', 255)->nullable();
            $table->text('client_secret')->nullable();

            $table->text('auth_url')->nullable();
            $table->text('token_url')->nullable();
            $table->text('redirect_uri')->nullable();
            $table->text('api_base_url')->nullable();

            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->text('id_token')->nullable();

            $table->string('token_type', 50)->nullable();
            $table->text('scope')->nullable();

            $table->integer('expires_in')->nullable();
            $table->timestamp('access_token_expires_at')->nullable()->index();
            $table->timestamp('refresh_token_expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();

            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();

            $table->json('settings')->nullable();
            $table->json('token_response')->nullable();
            $table->json('meta')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['company_id', 'provider', 'service_type', 'organization_id'],
                'unique_company_provider_service_org'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_accounts');
    }
};