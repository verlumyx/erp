<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera del expediente de costos de una importación: junta lo que costó
     * traer la mercancía con lo que llegó de ella, reparte lo primero entre lo
     * segundo y deja el inventario valorado al costo puesto en bodega.
     *
     * El expediente no toca el kardex. Confirmarlo genera un ajuste de tipo
     * `revaluation` en borrador, y es ese ajuste el que reexpresa el costo.
     */
    public function up(): void
    {
        Schema::create('app_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            /**
             * Dónde se revaloriza. Una sola por expediente: el ajuste que
             * genera lleva una sola bodega en su cabecera.
             */
            $table->uuid('warehouse_id');

            /** Fecha del expediente. Es la que lleva el ajuste que genera. */
            $table->date('import_date');
            $table->date('arrival_date')->nullable();

            /** Embarque, conocimiento de embarque o guía aérea. */
            $table->string('reference', 60)->nullable();

            /** Cómo se reparte el gasto entre lo que llegó. */
            $table->enum('allocation_method', ['value', 'quantity', 'weight', 'volume'])
                ->default('value');

            /** Moneda del expediente. A ella se convierte cada costo. */
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('base_currency', 3)->nullable();
            $table->decimal('base_exchange_rate', 18, 8)->nullable();

            /** Suma de los costos activos, ya convertidos a la moneda del expediente. */
            $table->decimal('total_charges', 18, 2)->default(0);

            /** Valor de la mercancía costeada, antes del reparto. */
            $table->decimal('total_base_value', 18, 2)->default(0);

            /** Lo que la mercancía vale puesta en bodega. */
            $table->decimal('total_landed_value', 18, 2)->default(0);

            /**
             * La parte del gasto que va al inventario, y la que no pudo
             * capitalizarse porque la mercancía ya salió. La primera es una
             * estimación: la cierra el ajuste contra la existencia del momento
             * en que se confirma.
             */
            $table->decimal('capitalized_amount', 18, 2)->default(0);
            $table->decimal('variance_amount', 18, 2)->default(0);

            /** El ajuste de revaluación que generó al confirmarse. */
            $table->uuid('adjustment_id')->nullable();

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

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            $table->foreign('adjustment_id')
                ->references('id')
                ->on('app_adjustments')
                ->nullOnDelete();

            /** Foreign key: quién registró el expediente (trazabilidad) */
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
            $table->index('warehouse_id');
            $table->index('import_date');
            $table->index('reference');
            $table->index('adjustment_id');

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('app_imports', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['adjustment_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_imports');
    }
};
