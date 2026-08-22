<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera de la factura de compra. Es el documento que genera la deuda
     * con el proveedor y, cuando no hubo entrada previa, el que mete la
     * mercancía al inventario.
     */
    public function up(): void
    {
        Schema::create('app_purchase_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->uuid('supplier_id');

            /**
             * Documento origen polimórfico. `sourceable_type` guarda el alias
             * del morph map (`purchase_order`), nunca el FQCN: mover o
             * renombrar la clase no rompe los datos ya guardados.
             */
            $table->string('sourceable_type', 255)->nullable();
            $table->uuid('sourceable_id')->nullable();

            /**
             * La entrada de mercancía es un documento paralelo, no el que
             * origina la factura: por eso queda fuera del morph. Sin foreign
             * key hasta que exista el módulo Entradas (`app_entries`).
             */
            $table->uuid('entry_id')->nullable();

            $table->uuid('warehouse_id');

            $table->string('supplier_invoice_number', 60);
            $table->string('supplier_invoice_series', 20)->nullable();

            $table->date('invoice_date');
            $table->date('received_date')->nullable();
            $table->date('due_date');

            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('base_currency', 3)->nullable();
            $table->decimal('base_exchange_rate', 18, 8)->nullable();

            /** `no` cuando el stock ya entró con una Entrada previa. */
            $table->enum('affects_inventory', ['yes', 'no'])->default('yes');

            /** Totales derivados de las líneas activas más los cargos globales. */
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('withholding_amount', 18, 2)->default(0);
            $table->decimal('freight_amount', 18, 2)->default(0);
            $table->decimal('other_charges', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            /**
             * Importes en bolívares congelados: la factura tiene valor legal y
             * su deuda en moneda local no se recalcula nunca. Solo en la
             * cabecera; las líneas viven en la moneda del documento.
             */
            $table->decimal('subtotal_ves', 18, 2)->default(0);
            $table->decimal('tax_amount_ves', 18, 2)->default(0);
            $table->decimal('total_ves', 18, 2)->default(0);

            /** Los mueven los Pagos, los Anticipos y las Notas de crédito. */
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->decimal('balance', 18, 2)->default(0);
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'overdue'])
                ->default('pending');

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

            $table->foreign('supplier_id')
                ->references('id')
                ->on('app_suppliers')
                ->restrictOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            /** Foreign key: quién registró la factura (trazabilidad) */
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
            $table->index('supplier_id');
            $table->index('warehouse_id');
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('payment_status');
            $table->index(['sourceable_type', 'sourceable_id']);

            $table->unique(['company_id', 'code']);

            /** El número impreso del proveedor no se repite: bloquea el duplicado. */
            $table->unique(['company_id', 'supplier_id', 'supplier_invoice_number'], 'app_purchase_invoices_supplier_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_purchase_invoices');
    }
};
