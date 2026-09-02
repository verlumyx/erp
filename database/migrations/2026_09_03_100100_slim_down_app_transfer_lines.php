<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La línea del traslado se queda con lo único que el traslado decide: qué
     * artículo se mueve, en qué unidad y cuánto.
     *
     * La ubicación de origen y la de destino repetían por línea lo que la
     * cabecera ya dice con sus dos bodegas. El lote y la serie se eligen ahora
     * al despachar, que es cuando alguien tiene la mercancía delante y puede
     * leer el número de la caja. Y las cantidades del viaje —lo enviado, lo
     * recibido y lo que faltó— las cuentan el despacho y la entrada que el
     * traslado genera: aquí ya no tienen quién las escriba.
     *
     * La bodega de tránsito desaparece con ellas: el tramo en camino lo dice
     * ahora el `delivery_status` del despacho.
     */
    public function up(): void
    {
        Schema::table('app_transfer_lines', function (Blueprint $table) {
            $table->dropForeign(['origin_location_id']);
            $table->dropForeign(['destination_location_id']);
            $table->dropForeign(['lot_id']);
            $table->dropForeign(['serial_id']);

            $table->dropColumn([
                'origin_location_id',
                'destination_location_id',
                'lot_id',
                'serial_id',
                'sent_quantity',
                'received_quantity',
                'difference_quantity',
            ]);
        });

        Schema::table('app_transfers', function (Blueprint $table) {
            $table->dropForeign(['transit_warehouse_id']);
            $table->dropColumn('transit_warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_transfer_lines', function (Blueprint $table) {
            $table->uuid('origin_location_id')->nullable()->after('measurement_unit_id');
            $table->uuid('destination_location_id')->nullable()->after('origin_location_id');
            $table->uuid('lot_id')->nullable()->after('destination_location_id');
            $table->uuid('serial_id')->nullable()->after('lot_id');
            $table->decimal('sent_quantity', 18, 4)->default(0);
            $table->decimal('received_quantity', 18, 4)->default(0);
            $table->decimal('difference_quantity', 18, 4)->default(0);

            $table->foreign('origin_location_id')->references('id')->on('app_warehouse_locations')->nullOnDelete();
            $table->foreign('destination_location_id')->references('id')->on('app_warehouse_locations')->nullOnDelete();
            $table->foreign('lot_id')->references('id')->on('app_item_lots')->restrictOnDelete();
            $table->foreign('serial_id')->references('id')->on('app_item_serials')->restrictOnDelete();
        });

        Schema::table('app_transfers', function (Blueprint $table) {
            $table->uuid('transit_warehouse_id')->nullable()->after('destination_warehouse_id');

            $table->foreign('transit_warehouse_id')->references('id')->on('app_warehouses')->restrictOnDelete();
        });
    }
};
