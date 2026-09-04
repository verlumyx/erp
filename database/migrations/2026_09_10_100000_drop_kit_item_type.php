<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private const WITHOUT_KIT = ['inventoried', 'non_inventoried', 'service', 'serialized'];

    /**
     * @var array<int, string>
     */
    private const WITH_KIT = ['inventoried', 'non_inventoried', 'service', 'kit', 'serialized'];

    /**
     * `kit` no llegó a significar nada: era el único tipo que ninguna regla
     * consultaba, la pantalla lo rotulaba «Lotes» y la documentación lo daba
     * por un compuesto de otros artículos. Con tres lecturas distintas y cero
     * comportamiento detrás, el tipo sobra.
     *
     * Lo que se creó eligiéndolo se quería inventariado: el lote nunca fue un
     * tipo de artículo sino una capacidad de cualquiera que mueva existencia,
     * y como `kit` quedaba fuera de los que admitían lote, elegirlo lograba
     * justo lo contrario de lo que prometía.
     */
    public function up(): void
    {
        DB::table('app_items')->where('type', 'kit')->update(['type' => 'inventoried']);

        $this->rewriteItemTypes(self::WITHOUT_KIT);
    }

    /**
     * Los artículos que se convirtieron no vuelven: `inventoried` es lo que de
     * verdad eran, y no hay forma de distinguirlos de los que ya lo estaban.
     */
    public function down(): void
    {
        $this->rewriteItemTypes(self::WITH_KIT);
    }

    /**
     * Un `enum` de Laravel no es un tipo propio: es un `varchar` con una
     * restricción `CHECK` que enumera los valores. PostgreSQL no sabe cambiarla
     * con un `ALTER COLUMN`, así que hay que reemplazar la restricción; el
     * resto de motores sí admiten redefinir la columna entera.
     *
     * @param  array<int, string>  $types
     */
    private function rewriteItemTypes(array $types): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            Schema::table('app_items', function (Blueprint $table) use ($types) {
                $table->enum('type', $types)->default('inventoried')->change();
            });

            return;
        }

        $values = implode(', ', array_map(
            static fn (string $type): string => "'{$type}'",
            $types,
        ));

        DB::statement('ALTER TABLE app_items DROP CONSTRAINT IF EXISTS app_items_type_check');

        DB::statement(
            "ALTER TABLE app_items ADD CONSTRAINT app_items_type_check CHECK (type IN ({$values}))"
        );
    }
};
