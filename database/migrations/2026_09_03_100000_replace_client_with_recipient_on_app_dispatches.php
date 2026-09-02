<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El despacho deja de ir siempre a un cliente.
     *
     * Un despacho de venta lleva la mercancía a un cliente, pero uno que sirve
     * un traslado la lleva a otra bodega de la propia empresa. Un `client_id`
     * obligatorio no sabe decir eso, así que el destinatario pasa a ser una
     * relación polimórfica: guarda el alias del morph map —`client` o
     * `warehouse`— y el id del destinatario.
     *
     * Es el mismo mecanismo que ya usa `sourceable` para el documento origen, y
     * por las mismas razones: el alias sobrevive a mover o renombrar la clase, y
     * mañana admite otro destinatario sin añadir una columna por cada uno.
     */
    public function up(): void
    {
        Schema::table('app_dispatches', function (Blueprint $table) {
            $table->string('recipient_type', 255)->nullable()->after('company_id');
            $table->uuid('recipient_id')->nullable()->after('recipient_type');

            $table->index(['recipient_type', 'recipient_id']);
        });

        /** Todo lo que existe hoy va dirigido a un cliente. */
        DB::table('app_dispatches')->update([
            'recipient_type' => 'client',
            'recipient_id' => DB::raw('client_id'),
        ]);

        Schema::table('app_dispatches', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropIndex(['client_id']);
            $table->dropColumn('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_dispatches', function (Blueprint $table) {
            $table->uuid('client_id')->nullable()->after('company_id');
        });

        DB::table('app_dispatches')
            ->where('recipient_type', 'client')
            ->update(['client_id' => DB::raw('recipient_id')]);

        Schema::table('app_dispatches', function (Blueprint $table) {
            $table->index('client_id');
            $table->dropIndex(['recipient_type', 'recipient_id']);
            $table->dropColumn(['recipient_type', 'recipient_id']);
        });
    }
};
