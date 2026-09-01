<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera de la entrada: recepción física de mercancía en bodega. Su
     * origen habitual es una orden de compra, pero también cubre las entradas
     * sin documento previo (producción, donación, inventario inicial).
     */
    public function up(): void
    {
        Schema::create('app_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            /** Nulo cuando la mercancía no viene de un proveedor. */
            $table->uuid('supplier_id')->nullable();

            /**
             * Documento origen: el par polimórfico `sourceable`. Guarda el
             * alias del morph map, no el FQCN, para que mover o renombrar la
             * clase no invalide lo ya escrito. Hoy solo `purchase_order`.
             */
            $table->string('sourceable_type', 255)->nullable();
            $table->uuid('sourceable_id')->nullable();

            /** Bodega de recepción. La línea elige la ubicación dentro de ella. */
            $table->uuid('warehouse_id');

            $table->date('entry_date');

            $table->enum('entry_type', [
                'purchase', 'production', 'return', 'donation', 'initial', 'other',
            ])->default('purchase');

            /** Remisión o guía con la que el proveedor despachó. */
            $table->string('supplier_document', 60)->nullable();
            $table->string('carrier', 150)->nullable();
            $table->string('tracking_number', 60)->nullable();

            $table->uuid('received_by')->nullable();
            $table->uuid('inspected_by')->nullable();

            $table->enum('inspection_status', ['pending', 'approved', 'rejected', 'partial'])
                ->default('pending');

            /**
             * La entrada no es un documento fiscal —lo es la factura de compra
             * que la respalda—, así que congela las cuatro tasas para reexpresar
             * su costo pero no persiste montos en bolívares.
             */
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('base_currency', 3)->nullable();
            $table->decimal('base_exchange_rate', 18, 8)->nullable();

            /** Suma de lo aceptado por las líneas activas, en unidad base. */
            $table->decimal('total_quantity', 18, 4)->default(0);

            /** Gastos capitalizables que se prorratean al costo de las líneas. */
            $table->decimal('freight_amount', 18, 2)->default(0);
            $table->decimal('other_charges', 18, 2)->default(0);

            /** Valor total ingresado: lo recibido más los gastos prorrateados. */
            $table->decimal('total_cost', 18, 2)->default(0);

            $table->enum('is_invoiced', ['yes', 'no'])->default('no');

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

            $table->foreign('supplier_id')
                ->references('id')
                ->on('app_suppliers')
                ->restrictOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            /** Foreign key: quién recibió la mercancía (trazabilidad) */
            $table->foreign('received_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /** Foreign key: quién hizo el control de calidad (trazabilidad) */
            $table->foreign('inspected_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /** Foreign key: quién registró la entrada (trazabilidad) */
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
            $table->index(['sourceable_type', 'sourceable_id']);
            $table->index('entry_date');
            $table->index('warehouse_id');
            $table->index('entry_type');
            $table->index('is_invoiced');

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('app_entries', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['received_by']);
            $table->dropForeign(['inspected_by']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_entries');
    }
};
