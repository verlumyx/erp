<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cabecera del documento fiscal: genera la cuenta por cobrar y descarga
     * inventario si no hubo despacho previo.
     */
    public function up(): void
    {
        Schema::create('app_sales_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->uuid('client_id');

            /**
             * Documento origen. No es un FK: `sourceable_type` guarda el alias
             * del morph map (hoy solo `sales_order`), de modo que mañana
             * admita otros orígenes sin agregar una columna por cada uno. La
             * integridad la valida el Service, no la base de datos.
             */
            $table->string('sourceable_type', 255)->nullable();
            $table->uuid('sourceable_id')->nullable();

            /**
             * Despacho asociado. Queda fuera del morph a propósito: la entrega
             * es un documento paralelo, no el que origina la factura. La
             * foreign key contra app_dispatches se agrega junto con el módulo
             * de Despachos (Logística), que aún no existe.
             */
            $table->uuid('dispatch_id')->nullable();

            $table->uuid('client_address_id')->nullable();
            $table->uuid('warehouse_id');
            $table->uuid('salesperson_id')->nullable();

            /** Serie fiscal autorizada. */
            $table->string('invoice_series', 20)->nullable();

            /**
             * Correlativo fiscal, distinto de `code`. Nulo mientras la factura
             * es borrador: el número se asigna al confirmar, nunca antes, para
             * no quemar un correlativo en un documento que puede no emitirse.
             */
            $table->string('invoice_number', 30)->nullable();

            $table->date('invoice_date');
            $table->date('due_date');

            $table->enum('sale_type', ['cash', 'credit'])->default('credit');

            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('base_currency', 3)->nullable();
            $table->decimal('base_exchange_rate', 18, 8)->nullable();

            /** `no` si el stock ya salió con un despacho. */
            $table->enum('affects_inventory', ['yes', 'no'])->default('yes');

            /** Totales derivados de las líneas activas: nunca se capturan a mano. */
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);

            /** Retención practicada por el cliente sobre el impuesto. */
            $table->decimal('withholding_amount', 18, 2)->default(0);

            /** Flete cobrado al cliente: suma al total. */
            $table->decimal('freight_amount', 18, 2)->default(0);

            $table->decimal('total', 18, 2)->default(0);

            /** Costo de la mercancía vendida; base del margen. */
            $table->decimal('total_cost', 18, 2)->default(0);

            /**
             * Importes en bolívares congelados al emitir. Solo los documentos
             * con valor legal los persisten (ver docs/monedas.md §4).
             */
            $table->decimal('subtotal_ves', 18, 2)->default(0);
            $table->decimal('tax_amount_ves', 18, 2)->default(0);
            $table->decimal('total_ves', 18, 2)->default(0);

            /** Cobrado + anticipos + notas de crédito aplicadas. */
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->decimal('balance', 18, 2)->default(0);

            $table->enum('payment_status', ['pending', 'partial', 'paid', 'overdue'])
                ->default('pending');

            /** Facturación electrónica: nulo mientras no se transmite. */
            $table->enum('fiscal_status', ['pending', 'sent', 'accepted', 'rejected'])->nullable();
            $table->string('fiscal_uuid', 100)->nullable();
            $table->timestamp('printed_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'confirmed', 'completed', 'cancelled'])
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

            $table->foreign('salesperson_id')
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
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('payment_status');
            $table->index('salesperson_id');
            $table->index('warehouse_id');
            $table->index(['sourceable_type', 'sourceable_id']);

            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'invoice_series', 'invoice_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['client_address_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['salesperson_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_sales_invoices');
    }
};
