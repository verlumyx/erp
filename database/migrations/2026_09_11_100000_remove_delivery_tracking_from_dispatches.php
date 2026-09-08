<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El despacho deja de llevar un eje de entrega propio.
     *
     * Confirmar saca la mercancía; marcar entregado cierra el viaje. Lo que el
     * cliente no reciba —o rechace— ya no se anota aquí: se resuelve con una
     * devolución de venta, que es el documento que sabe devolver la mercancía y
     * el dinero. Por eso se van `delivery_status`, las cantidades entregada y
     * devuelta de cada línea, y toda la constancia de la entrega.
     *
     * El estado terminal `completed` pasa a llamarse `delivered`: el camino del
     * documento es Borrador → Confirmado → Entregado.
     */
    public function up(): void
    {
        Schema::table('app_dispatches', function (Blueprint $table): void {
            $table->dropIndex(['delivery_status']);
            $table->dropColumn([
                'delivery_status',
                'received_by_name',
                'received_by_document',
                'signature_path',
                'evidence_path',
                'latitude',
                'longitude',
                'rejection_reason',
            ]);
        });

        Schema::table('app_dispatch_lines', function (Blueprint $table): void {
            $table->dropColumn(['delivered_quantity', 'returned_quantity']);
        });

        $this->rewriteStatuses(
            ['draft', 'confirmed', 'completed', 'delivered', 'cancelled'],
            ['draft', 'confirmed', 'delivered', 'cancelled'],
            from: 'completed',
            to: 'delivered',
        );
    }

    public function down(): void
    {
        $this->rewriteStatuses(
            ['draft', 'confirmed', 'completed', 'delivered', 'cancelled'],
            ['draft', 'confirmed', 'completed', 'cancelled'],
            from: 'delivered',
            to: 'completed',
        );

        Schema::table('app_dispatches', function (Blueprint $table): void {
            $table->enum('delivery_status', [
                'pending', 'in_transit', 'delivered', 'partial_delivered', 'rejected', 'returned',
            ])->default('pending');

            $table->string('received_by_name', 150)->nullable();
            $table->string('received_by_document', 30)->nullable();
            $table->string('signature_path', 500)->nullable();
            $table->string('evidence_path', 500)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('rejection_reason', 500)->nullable();

            $table->index('delivery_status');
        });

        Schema::table('app_dispatch_lines', function (Blueprint $table): void {
            $table->decimal('delivered_quantity', 18, 4)->default(0);
            $table->decimal('returned_quantity', 18, 4)->default(0);
        });
    }

    /**
     * Renombra un valor del enum `status` migrando de paso las filas que lo
     * llevan. Postgres no admite cambiar la restricción `check` con el
     * `->change()` de Laravel, así que ahí se reescribe el constraint a mano;
     * en sqlite y MySQL el propio `->change()` reconstruye la columna.
     *
     * @param  array<int, string>  $wide  Valores admitidos mientras conviven el viejo y el nuevo.
     * @param  array<int, string>  $narrow  Valores finales, ya sin el viejo.
     */
    private function rewriteStatuses(array $wide, array $narrow, string $from, string $to): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_dispatches DROP CONSTRAINT IF EXISTS app_dispatches_status_check');
            DB::table('app_dispatches')->where('status', $from)->update(['status' => $to]);

            $values = implode(', ', array_map(static fn (string $value): string => "'{$value}'", $narrow));
            DB::statement("ALTER TABLE app_dispatches ADD CONSTRAINT app_dispatches_status_check CHECK (status IN ({$values}))");

            return;
        }

        Schema::table('app_dispatches', function (Blueprint $table) use ($wide): void {
            $table->enum('status', $wide)->default('draft')->change();
        });

        DB::table('app_dispatches')->where('status', $from)->update(['status' => $to]);

        Schema::table('app_dispatches', function (Blueprint $table) use ($narrow): void {
            $table->enum('status', $narrow)->default('draft')->change();
        });
    }
};
