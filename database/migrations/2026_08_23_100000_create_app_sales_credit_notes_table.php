<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera de la nota de crédito a cliente. Disminuye la cuenta por cobrar:
     * devoluciones, descuentos posteriores o correcciones de precio.
     */
    public function up(): void
    {
        Schema::create('app_sales_credit_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->uuid('client_id');

            /** Factura afectada. Una nota puede nacer sin ella (descuento global). */
            $table->uuid('sales_invoice_id')->nullable();

            /**
             * Devolución que origina la nota. Sin foreign key: `app_sales_returns`
             * se crea después y apunta de vuelta con su `credit_note_id`.
             */
            $table->uuid('sales_return_id')->nullable();

            /** Serie fiscal autorizada. */
            $table->string('note_series', 20)->nullable();

            /**
             * Correlativo fiscal, distinto de `code`. Nulo mientras la nota es
             * borrador: el número se quema al confirmar, nunca antes, para no
             * gastar un correlativo en un documento que puede no emitirse.
             */
            $table->string('note_number', 30)->nullable();

            $table->date('note_date');

            $table->enum('reason', ['return', 'discount', 'price_correction', 'damaged', 'cancellation', 'other'])
                ->default('return');
            $table->string('reason_detail', 500)->nullable();

            /** `yes` cuando la mercancía reingresa a la bodega. */
            $table->enum('affects_inventory', ['yes', 'no'])->default('no');

            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('base_currency', 3)->nullable();
            $table->decimal('base_exchange_rate', 18, 8)->nullable();

            /** Totales derivados de las líneas activas: nunca se capturan a mano. */
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            /**
             * Importes en bolívares congelados: la nota de crédito es un
             * documento fiscal y su valor en moneda local no se recalcula.
             * Solo en la cabecera; las líneas viven en la moneda del documento.
             */
            $table->decimal('subtotal_ves', 18, 2)->default(0);
            $table->decimal('tax_amount_ves', 18, 2)->default(0);
            $table->decimal('total_ves', 18, 2)->default(0);

            /** Los mueven las aplicaciones a facturas (`credit_note`). */
            $table->decimal('applied_amount', 18, 2)->default(0);
            $table->decimal('balance', 18, 2)->default(0);

            /** Facturación electrónica: nulo mientras no se transmite. */
            $table->enum('fiscal_status', ['pending', 'sent', 'accepted', 'rejected'])->nullable();
            $table->string('fiscal_uuid', 100)->nullable();

            $table->timestamp('cancelled_at')->nullable();
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

            $table->foreign('sales_invoice_id')
                ->references('id')
                ->on('app_sales_invoices')
                ->restrictOnDelete();

            /** Foreign key: quién registró la nota (trazabilidad) */
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
            $table->index('sales_invoice_id');
            $table->index('note_date');
            $table->index('balance');

            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'note_series', 'note_number']);
        });
    }

    public function down(): void
    {
        Schema::table('app_sales_credit_notes', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['sales_invoice_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_sales_credit_notes');
    }
};
