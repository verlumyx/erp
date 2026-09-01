<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seis tablas ya guardaban `route_id` —y el despacho además
     * `route_stop_id`— pero sin foreign key: `app_routes` no existía todavía.
     * Ahora sí, así que la integridad la garantiza la base y no cada Service.
     *
     * `nullOnDelete` y no `restrictOnDelete`: la ruta es planificación, no un
     * documento. Si algún día desapareciera, el cobro que se hizo por ella
     * seguiría siendo válido; simplemente dejaría de estar asignado.
     */
    private const ROUTE_COLUMN_TABLES = [
        'app_clients',
        'app_client_addresses',
        'app_sales_orders',
        'app_client_collections',
        'app_dispatches',
        'app_transfers',
    ];

    public function up(): void
    {
        foreach (self::ROUTE_COLUMN_TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->foreign('route_id')
                    ->references('id')
                    ->on('app_routes')
                    ->nullOnDelete();
            });
        }

        Schema::table('app_dispatches', function (Blueprint $table) {
            $table->foreign('route_stop_id')
                ->references('id')
                ->on('app_route_stops')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('app_dispatches', function (Blueprint $table) {
            $table->dropForeign(['route_stop_id']);
        });

        foreach (self::ROUTE_COLUMN_TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropForeign(['route_id']);
            });
        }
    }
};
