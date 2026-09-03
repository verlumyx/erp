<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El flete y los otros gastos salen de las cabeceras que los llevaban.
     *
     * En la Entrada eran el prorrateo que valoraba la mercancía, y ese trabajo
     * pasa al expediente de Importaciones: el costo de traerla se sabe después,
     * en papeles distintos y casi siempre de terceros distintos, y la entrada no
     * es el sitio donde ese número existe. El `landed_cost` de la línea queda
     * igual a su `unit_cost` hasta que un expediente lo revalorice.
     *
     * En las dos facturas eran un importe de cabecera que sumaba al total. Ahí
     * el flete no desaparece: se cobra como una **línea** con un artículo de
     * tipo `service`, igual que cualquier otro cargo del mismo papel. Así el
     * total sigue siendo lo que se debe —o lo que se cobra— y el cargo queda
     * escrito donde se puede gravar, descontar y rastrear.
     *
     * En el Despacho no calculaba nada: era un dato suelto que nadie leía.
     */
    public function up(): void
    {
        Schema::table('app_purchase_invoices', function (Blueprint $table): void {
            $table->dropColumn(['freight_amount', 'other_charges']);
        });

        Schema::table('app_sales_invoices', function (Blueprint $table): void {
            $table->dropColumn('freight_amount');
        });

        Schema::table('app_entries', function (Blueprint $table): void {
            $table->dropColumn(['freight_amount', 'other_charges']);
        });

        Schema::table('app_dispatches', function (Blueprint $table): void {
            $table->dropColumn('freight_amount');
        });
    }

    public function down(): void
    {
        Schema::table('app_purchase_invoices', function (Blueprint $table): void {
            $table->decimal('freight_amount', 18, 2)->default(0)->after('withholding_amount');
            $table->decimal('other_charges', 18, 2)->default(0)->after('freight_amount');
        });

        Schema::table('app_sales_invoices', function (Blueprint $table): void {
            $table->decimal('freight_amount', 18, 2)->default(0)->after('withholding_amount');
        });

        Schema::table('app_entries', function (Blueprint $table): void {
            $table->decimal('freight_amount', 18, 2)->default(0)->after('total_quantity');
            $table->decimal('other_charges', 18, 2)->default(0)->after('freight_amount');
        });

        Schema::table('app_dispatches', function (Blueprint $table): void {
            $table->decimal('freight_amount', 18, 2)->default(0)->after('tracking_number');
        });
    }
};
