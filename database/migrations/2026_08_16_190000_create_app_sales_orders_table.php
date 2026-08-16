<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cabecera del pedido del cliente. No descarga inventario: lo reserva a
     * través de `reserved_quantity` en las líneas.
     */
    public function up(): void
    {
        Schema::create('app_sales_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->uuid('client_id');
            $table->uuid('client_address_id')->nullable();
            $table->uuid('warehouse_id');
            $table->uuid('price_list_id')->nullable();
            $table->uuid('salesperson_id')->nullable();

            /**
             * Ruta de entrega. La foreign key contra app_routes se agrega junto
             * con el módulo de Rutas (Logística), que aún no existe.
             */
            $table->uuid('route_id')->nullable();

            $table->date('order_date');
            $table->date('expected_date')->nullable();

            /** Número de orden de compra del cliente. */
            $table->string('client_reference', 60)->nullable();

            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->integer('payment_term_days')->default(0);

            /** Totales derivados de las líneas activas: nunca se capturan a mano. */
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            /** Avance de despacho y de facturación (0–100). */
            $table->decimal('dispatched_percent', 7, 4)->default(0);
            $table->decimal('invoiced_percent', 7, 4)->default(0);

            /** Necesario si el pedido excede el límite de crédito del cliente. */
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'confirmed', 'partial', 'completed', 'cancelled'])
                ->default('draft');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('client_id')
                ->references('id')
                ->on('app_clients')
                ->restrictOnDelete();

            $table->foreign('client_address_id')
                ->references('id')
                ->on('app_client_addresses')
                ->nullOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            $table->foreign('price_list_id')
                ->references('id')
                ->on('app_price_lists')
                ->nullOnDelete();

            $table->foreign('salesperson_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');

            // Índices propios del módulo
            $table->index('client_id');
            $table->index('order_date');
            $table->index('expected_date');
            $table->index('salesperson_id');
            $table->index('route_id');
            $table->index('warehouse_id');

            $table->unique(['company_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_sales_orders', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['client_address_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['price_list_id']);
            $table->dropForeign(['salesperson_id']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_sales_orders');
    }
};
