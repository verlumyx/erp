<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pedido web: lo que el comprador envía desde el carrito. No afecta
     * inventario, saldos ni clientes: es una bandeja que un usuario del ERP
     * revisa y convierte en orden de venta. No se edita: se rechaza y se
     * hace otro.
     */
    public function up(): void
    {
        Schema::create('app_store_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            /** Quién compró. Siempre presente: no hay compra como invitado. */
            $table->uuid('store_customer_id');

            /** Copiado del comprador si ya estaba vinculado; si no, se llena al convertir. */
            $table->uuid('client_id')->nullable();

            /** Dirección elegida por un comprador vinculado. Nula si escribió una nueva. */
            $table->uuid('client_address_id')->nullable();

            /** La orden generada al convertir. */
            $table->uuid('sales_order_id')->nullable();

            /** Copia de los datos del comprador al momento del pedido. */
            $table->string('buyer_name', 150);
            $table->enum('buyer_document_type', ['V', 'E', 'J', 'P', 'G', 'C'])->nullable();
            $table->string('buyer_document_number', 15)->nullable();
            $table->string('buyer_email', 150);
            $table->string('buyer_phone', 30);

            /** Dirección de entrega en texto libre. */
            $table->string('delivery_address', 500)->nullable();
            $table->string('delivery_city', 100)->nullable();
            $table->string('delivery_state', 100)->nullable();

            /** Moneda de los precios mostrados y tasa del día, congelada. */
            $table->string('currency', 3);
            $table->decimal('exchange_rate', 18, 8)->default(1);

            /** Sin impuestos: la tienda muestra precios de lista. */
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            $table->text('buyer_notes')->nullable();

            $table->uuid('converted_by')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();

            /** Notas internas del usuario del ERP. */
            $table->text('notes')->nullable();

            $table->enum('status', ['pending', 'converted', 'rejected'])->default('pending');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('store_customer_id')
                ->references('id')
                ->on('app_store_customers')
                ->restrictOnDelete();

            $table->foreign('client_id')
                ->references('id')
                ->on('app_clients')
                ->restrictOnDelete();

            $table->foreign('client_address_id')
                ->references('id')
                ->on('app_client_addresses')
                ->restrictOnDelete();

            $table->foreign('sales_order_id')
                ->references('id')
                ->on('app_sales_orders')
                ->restrictOnDelete();

            $table->foreign('converted_by')
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
            $table->unique(['company_id', 'code']);

            // Índices propios del módulo
            $table->index('store_customer_id');
            $table->index('client_id');
            $table->index('sales_order_id');
            $table->index('buyer_email');
        });
    }

    public function down(): void
    {
        Schema::table('app_store_orders', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['store_customer_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['client_address_id']);
            $table->dropForeign(['sales_order_id']);
            $table->dropForeign(['converted_by']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_store_orders');
    }
};
