<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Saca el lote y las series de la línea de la entrada y los pasa a sus
     * tablas de detalle.
     *
     * Lo ya capturado se conserva: cada línea con lote produce una fila de lote
     * por lo que aceptó, y cada número de la lista de series produce su fila
     * colgada de ese lote.
     */
    public function up(): void
    {
        /**
         * Una base estrenada después de este cambio nunca tuvo esas columnas:
         * las quitó la propia migración que crea la tabla. Aquí solo hay algo
         * que hacer en las que ya venían de antes.
         */
        if (! Schema::hasColumn('app_entry_lines', 'lot_id')) {
            return;
        }

        $this->copyTraceability();

        /** SQLite no sabe soltar una foreign key; tampoco le hace falta. */
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('app_entry_lines', function (Blueprint $table) {
                $table->dropForeign(['lot_id']);
            });
        }

        Schema::table('app_entry_lines', function (Blueprint $table) {
            $table->dropColumn(['lot_number', 'lot_id', 'expires_at', 'serial_numbers']);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('app_entry_lines', 'lot_id')) {
            return;
        }

        Schema::table('app_entry_lines', function (Blueprint $table) {
            $table->string('lot_number', 60)->nullable();
            $table->uuid('lot_id')->nullable();
            $table->date('expires_at')->nullable();
            $table->json('serial_numbers')->nullable();
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('app_entry_lines', function (Blueprint $table) {
                $table->foreign('lot_id')
                    ->references('id')
                    ->on('app_item_lots')
                    ->restrictOnDelete();
            });
        }

        $this->restoreTraceability();
    }

    /**
     * Un lote por línea —que es todo lo que el esquema viejo admitía— con la
     * cantidad que esa línea aceptó, y sus series colgando de él.
     */
    private function copyTraceability(): void
    {
        $lines = DB::table('app_entry_lines')
            ->whereNotNull('lot_number')
            ->orWhereNotNull('lot_id')
            ->orWhereNotNull('serial_numbers')
            ->get();

        $now = now();

        foreach ($lines as $line) {
            $lotId = null;
            $hasLot = filled($line->lot_number) || filled($line->lot_id);

            if ($hasLot) {
                $lotId = (string) Str::uuid7();

                DB::table('app_entry_line_lots')->insert([
                    'id' => $lotId,
                    'company_id' => $line->company_id,
                    'entry_line_id' => $line->id,
                    'line_number' => 1,
                    'lot_number' => (string) ($line->lot_number ?? ''),
                    'lot_id' => $line->lot_id,
                    'expires_at' => $line->expires_at,
                    'quantity' => $line->received_quantity > 0 ? $line->received_quantity : $line->quantity,
                    'base_quantity' => 0,
                    'status' => $line->status,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $serials = $this->serialsOf($line->serial_numbers);

            foreach ($serials as $index => $serialNumber) {
                DB::table('app_entry_line_serials')->insert([
                    'id' => (string) Str::uuid7(),
                    'company_id' => $line->company_id,
                    'entry_line_id' => $line->id,
                    'entry_line_lot_id' => $lotId,
                    'line_number' => $index + 1,
                    'serial_number' => $serialNumber,
                    'serial_id' => null,
                    'status' => $line->status,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * La vuelta atrás solo puede devolver el primer lote de cada línea: el
     * esquema viejo no sabía guardar más de uno.
     */
    private function restoreTraceability(): void
    {
        $lots = DB::table('app_entry_line_lots')->orderBy('line_number')->get();

        foreach ($lots as $lot) {
            DB::table('app_entry_lines')
                ->where('id', $lot->entry_line_id)
                ->whereNull('lot_number')
                ->whereNull('lot_id')
                ->update([
                    'lot_number' => $lot->lot_number === '' ? null : $lot->lot_number,
                    'lot_id' => $lot->lot_id,
                    'expires_at' => $lot->expires_at,
                ]);
        }

        $serials = DB::table('app_entry_line_serials')
            ->orderBy('line_number')
            ->get()
            ->groupBy('entry_line_id');

        foreach ($serials as $entryLineId => $rows) {
            DB::table('app_entry_lines')
                ->where('id', $entryLineId)
                ->update([
                    'serial_numbers' => json_encode($rows->pluck('serial_number')->all()),
                ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function serialsOf(mixed $value): array
    {
        if (blank($value)) {
            return [];
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $serial): string => trim((string) $serial),
            $decoded,
        ), static fn (string $serial): bool => $serial !== ''));
    }
};
