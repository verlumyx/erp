<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La línea de la devolución dice de qué bodega sale —o a cuál vuelve— la
     * mercancía.
     *
     * La cabecera conserva la suya: es la del despacho o la entrada que la
     * devolución genera al confirmarse. La de la línea nace igual a esa y se
     * puede cambiar, igual que en las notas de crédito, para que una devolución
     * no obligue a partirse en dos cuando la mercancía salió de dos sitios.
     *
     * Las filas que ya existen heredan la bodega de su cabecera: hasta hoy no
     * había otra.
     */
    public function up(): void
    {
        $this->addWarehouse('app_purchase_return_lines', 'app_purchase_returns', 'purchase_return_id');
        $this->addWarehouse('app_sales_return_lines', 'app_sales_returns', 'sales_return_id');
    }

    public function down(): void
    {
        foreach (['app_purchase_return_lines', 'app_sales_return_lines'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['warehouse_id']);
                $blueprint->dropIndex(['warehouse_id']);
                $blueprint->dropColumn('warehouse_id');
            });
        }
    }

    /**
     * Añade la columna y la rellena con la bodega de la cabecera. Queda
     * `nullable` en la base —las filas antiguas de una cabecera ya borrada no
     * tendrían de dónde heredarla—; que venga siempre lo exige el Request.
     */
    private function addWarehouse(string $lines, string $header, string $foreignKey): void
    {
        Schema::table($lines, function (Blueprint $table) use ($foreignKey) {
            $table->uuid('warehouse_id')->nullable()->after($foreignKey);

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('app_warehouses')
                ->restrictOnDelete();

            $table->index('warehouse_id');
        });

        DB::table($lines)->update([
            'warehouse_id' => DB::raw(
                "(select warehouse_id from {$header} where {$header}.id = {$lines}.{$foreignKey})"
            ),
        ]);
    }
};
