<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajustes de la tienda en línea: una sola fila por empresa, como
     * `app_configurations`. Se crea sola la primera vez que se abre la
     * pantalla y nunca se lista ni se desactiva.
     */
    public function up(): void
    {
        Schema::create('app_store_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');

            /** Interruptor general: con `no` la API pública responde 403 a todo. */
            $table->enum('is_enabled', ['yes', 'no'])->default('no');

            $table->string('store_name', 150);
            $table->string('logo_path', 255)->nullable();

            /** Único color de acento de la tienda, en `#RRGGBB`. */
            $table->string('brand_color', 7)->default('#111827');

            /** Lista con la que se muestran los precios. Sin lista no hay precios. */
            $table->uuid('price_list_id')->nullable();

            /** Bodega contra la que se calcula la disponibilidad. Nula = todas. */
            $table->uuid('warehouse_id')->nullable();

            $table->enum('shows_stock', ['yes', 'no'])->default('no');
            $table->enum('allows_orders', ['yes', 'no'])->default('no');

            /** Tipo con el que se crea un cliente desde un pedido web. */
            $table->uuid('default_client_type_id')->nullable();

            $table->enum('shows_secondary_currency', ['yes', 'no'])->default('yes');

            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email', 150)->nullable();

            /** URL pública de la tienda; arma el enlace de invitación. */
            $table->string('store_url', 255)->nullable();

            /** SHA-256 de la llave. La llave en claro solo se muestra al generarla. */
            $table->string('api_key_hash', 64)->nullable();
            $table->timestamp('api_key_last_used_at')->nullable();

            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->cascadeOnDelete();

            $table->foreign('price_list_id')
                ->references('id')
                ->on('app_price_lists')
                ->restrictOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            $table->foreign('default_client_type_id')
                ->references('id')
                ->on('app_client_types')
                ->restrictOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique('company_id');
            $table->index('api_key_hash');
        });
    }

    public function down(): void
    {
        Schema::table('app_store_settings', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['price_list_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['default_client_type_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_store_settings');
    }
};
