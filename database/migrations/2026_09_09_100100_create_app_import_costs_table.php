<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lo que se está repartiendo: una fila por cada cobro, con el documento
     * que lo respalda y quién lo cobró.
     *
     * Quien cobra no tiene por qué ser el proveedor de la mercancía: el
     * transportista factura el flete y el agente factura la aduana. Una fila
     * que apunta a la factura de uno de ellos toma su total; una que apunta a
     * la del proveedor de la mercancía toma solo sus líneas de servicio,
     * porque el resto ya lo costeó la entrada.
     */
    public function up(): void
    {
        Schema::create('app_import_costs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('import_id');

            $table->integer('line_number');

            /**
             * Documento que respalda el cobro. No es un FK: es una relación
             * polimórfica, para que mañana admita otros papeles sin una
             * columna por cada uno. Hoy solo `purchase_invoice`.
             */
            $table->string('sourceable_type', 255)->nullable();
            $table->uuid('sourceable_id')->nullable();

            /** Quién cobra. No tiene por qué ser el de la mercancía. */
            $table->uuid('supplier_id')->nullable();

            $table->enum('concept', [
                'freight', 'insurance', 'customs', 'handling', 'storage', 'other',
            ])->default('freight');

            /** Obligatoria cuando el concepto es `other`. */
            $table->string('description', 500)->nullable();

            /** Moneda en la que se cobró, y su tasa a la del expediente. */
            $table->string('currency', 3);
            $table->decimal('exchange_rate', 18, 8)->default(1);

            $table->decimal('amount', 18, 2)->default(0);

            /** `amount * exchange_rate`. Es lo que entra al reparto. */
            $table->decimal('converted_amount', 18, 2)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('import_id')
                ->references('id')
                ->on('app_imports')
                ->cascadeOnDelete();

            $table->foreign('supplier_id')
                ->references('id')
                ->on('app_suppliers')
                ->restrictOnDelete();

            $table->index('import_id');
            $table->index(['sourceable_type', 'sourceable_id']);
            $table->index('supplier_id');
            $table->index('concept');
            $table->index('company_id');
            $table->index('status');

            $table->unique(['import_id', 'line_number'], 'app_import_costs_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_import_costs', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['import_id']);
            $table->dropForeign(['supplier_id']);
        });

        Schema::dropIfExists('app_import_costs');
    }
};
