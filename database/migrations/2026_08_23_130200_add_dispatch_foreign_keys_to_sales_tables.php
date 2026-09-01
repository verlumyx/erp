<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las facturas y las devoluciones de venta ya guardaban `dispatch_id`, pero
     * sin foreign key: la tabla `app_dispatches` no existía todavía. Ahora sí,
     * así que la integridad la garantiza la base y no el Service.
     *
     * `restrictOnDelete` como el resto de los documentos: la política de no
     * borrado no elimina un despacho que otro papel referencia.
     */
    public function up(): void
    {
        Schema::table('app_sales_invoices', function (Blueprint $table) {
            $table->foreign('dispatch_id')
                ->references('id')
                ->on('app_dispatches')
                ->restrictOnDelete();
        });

        Schema::table('app_sales_returns', function (Blueprint $table) {
            $table->foreign('dispatch_id')
                ->references('id')
                ->on('app_dispatches')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('app_sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['dispatch_id']);
        });

        Schema::table('app_sales_returns', function (Blueprint $table) {
            $table->dropForeign(['dispatch_id']);
        });
    }
};
