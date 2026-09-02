<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El cobro —o el pago— que hizo nacer un anticipo por su excedente.
 *
 * Con `origin_type` `client` o `invoice`, lo que sobra sin aplicar al confirmar
 * un `COB`/`PGP` se convierte en un anticipo ya confirmado: el dinero entró (o
 * salió) con ese documento y no vuelve a pedir aprobación
 * (`docs/ventas.md` §6.2, `docs/compras.md` §6.2).
 *
 * La columna es lo que permite deshacerlo: anular el cobro anula el anticipo
 * que generó. Es nula en un anticipo capturado a mano, que es el caso normal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_client_advances', function (Blueprint $table) {
            $table->uuid('origin_collection_id')->nullable()->after('sales_order_id');

            $table->foreign('origin_collection_id')
                ->references('id')
                ->on('app_client_collections')
                ->nullOnDelete();

            $table->index('origin_collection_id');
        });

        Schema::table('app_supplier_advances', function (Blueprint $table) {
            $table->uuid('origin_payment_id')->nullable()->after('purchase_order_id');

            $table->foreign('origin_payment_id')
                ->references('id')
                ->on('app_supplier_payments')
                ->nullOnDelete();

            $table->index('origin_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_client_advances', function (Blueprint $table) {
            $table->dropForeign(['origin_collection_id']);
            $table->dropIndex(['origin_collection_id']);
            $table->dropColumn('origin_collection_id');
        });

        Schema::table('app_supplier_advances', function (Blueprint $table) {
            $table->dropForeign(['origin_payment_id']);
            $table->dropIndex(['origin_payment_id']);
            $table->dropColumn('origin_payment_id');
        });
    }
};
