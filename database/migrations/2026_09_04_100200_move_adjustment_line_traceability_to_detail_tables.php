<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Saca el lote y la serie de la línea del ajuste y los pasa a sus tablas de
     * detalle. El esquema viejo solo admitía uno de cada, así que el trasvase
     * es fila a fila.
     */
    public function up(): void
    {
        /**
         * Una base estrenada después de este cambio nunca tuvo esas columnas:
         * las quitó la propia migración que crea la tabla.
         */
        if (! Schema::hasColumn('app_adjustment_lines', 'lot_id')) {
            return;
        }

        $this->copyTraceability();

        /** SQLite no sabe soltar una foreign key; tampoco le hace falta. */
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('app_adjustment_lines', function (Blueprint $table) {
                $table->dropForeign(['lot_id']);
                $table->dropForeign(['serial_id']);
            });
        }

        Schema::table('app_adjustment_lines', function (Blueprint $table) {
            $table->dropColumn(['lot_id', 'serial_id']);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('app_adjustment_lines', 'lot_id')) {
            return;
        }

        Schema::table('app_adjustment_lines', function (Blueprint $table) {
            $table->uuid('lot_id')->nullable();
            $table->uuid('serial_id')->nullable();
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('app_adjustment_lines', function (Blueprint $table) {
                $table->foreign('lot_id')
                    ->references('id')
                    ->on('app_item_lots')
                    ->restrictOnDelete();

                $table->foreign('serial_id')
                    ->references('id')
                    ->on('app_item_serials')
                    ->restrictOnDelete();
            });
        }

        $this->restoreTraceability();
    }

    /**
     * La línea vieja contaba un solo lote, así que lo que contó es exactamente
     * lo suyo: la fila de detalle hereda sus cantidades y su cálculo tal cual.
     */
    private function copyTraceability(): void
    {
        $lines = DB::table('app_adjustment_lines')
            ->whereNotNull('lot_id')
            ->orWhereNotNull('serial_id')
            ->get();

        $now = now();

        foreach ($lines as $line) {
            $lotRowId = null;

            if (filled($line->lot_id)) {
                $lotRowId = (string) Str::uuid7();

                DB::table('app_adjustment_line_lots')->insert([
                    'id' => $lotRowId,
                    'company_id' => $line->company_id,
                    'adjustment_line_id' => $line->id,
                    'line_number' => 1,
                    'lot_id' => $line->lot_id,
                    'counted_quantity' => $line->counted_quantity,
                    'system_quantity' => $line->system_quantity,
                    'difference_quantity' => $line->difference_quantity,
                    'base_quantity' => $line->base_quantity,
                    'movement_type' => $line->movement_type,
                    'unit_cost' => $line->unit_cost,
                    'total_cost' => $line->total_cost,
                    'status' => $line->status,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if (filled($line->serial_id)) {
                DB::table('app_adjustment_line_serials')->insert([
                    'id' => (string) Str::uuid7(),
                    'company_id' => $line->company_id,
                    'adjustment_line_id' => $line->id,
                    'adjustment_line_lot_id' => $lotRowId,
                    'line_number' => 1,
                    'serial_id' => $line->serial_id,
                    'status' => $line->status,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Solo vuelve el primer lote y la primera serie de cada línea: es lo único
     * que el esquema viejo sabe guardar.
     */
    private function restoreTraceability(): void
    {
        $lots = DB::table('app_adjustment_line_lots')->orderBy('line_number')->get();

        foreach ($lots as $lot) {
            DB::table('app_adjustment_lines')
                ->where('id', $lot->adjustment_line_id)
                ->whereNull('lot_id')
                ->update(['lot_id' => $lot->lot_id]);
        }

        $serials = DB::table('app_adjustment_line_serials')->orderBy('line_number')->get();

        foreach ($serials as $serial) {
            DB::table('app_adjustment_lines')
                ->where('id', $serial->adjustment_line_id)
                ->whereNull('serial_id')
                ->update(['serial_id' => $serial->serial_id]);
        }
    }
};
