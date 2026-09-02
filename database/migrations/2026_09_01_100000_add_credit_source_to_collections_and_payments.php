<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De qué crédito sale un cobro —o un pago— que no trae dinero.
 *
 * Con `payment_method` `advance` o `credit_note` lo que cancela la factura no
 * es caja sino un saldo a favor que ya existe: un anticipo `ANC`/`ANP` o una
 * nota de crédito `NCC`/`NCP`. Esta columna dice cuál, y es la que decide con
 * qué `source_type` se escriben las filas del reparto
 * (`docs/ventas.md` §6.3, `docs/compras.md` §6.3).
 *
 * Sin foreign key: apunta a dos tablas distintas según la forma de cobro, igual
 * que `origin_id`, y lo valida el Service antes de guardar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_client_collections', function (Blueprint $table) {
            $table->uuid('credit_source_id')->nullable()->after('origin_id');
            $table->index('credit_source_id');
        });

        Schema::table('app_supplier_payments', function (Blueprint $table) {
            $table->uuid('credit_source_id')->nullable()->after('origin_id');
            $table->index('credit_source_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_client_collections', function (Blueprint $table) {
            $table->dropIndex(['credit_source_id']);
            $table->dropColumn('credit_source_id');
        });

        Schema::table('app_supplier_payments', function (Blueprint $table) {
            $table->dropIndex(['credit_source_id']);
            $table->dropColumn('credit_source_id');
        });
    }
};
