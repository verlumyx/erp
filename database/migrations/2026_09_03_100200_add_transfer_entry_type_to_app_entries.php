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
    private const WITH_TRANSFER = [
        'purchase', 'production', 'return', 'donation', 'initial', 'transfer', 'other',
    ];

    /**
     * @var array<int, string>
     */
    private const WITHOUT_TRANSFER = [
        'purchase', 'production', 'return', 'donation', 'initial', 'other',
    ];

    /**
     * La mercancía que llega desde otra bodega propia no se compra, no se
     * produce y no la regala nadie: es un traslado. El tipo lo dice, y con él
     * la entrada sabe que no lleva proveedor detrás.
     */
    public function up(): void
    {
        $this->rewriteEntryTypes(self::WITH_TRANSFER);
    }

    public function down(): void
    {
        $this->rewriteEntryTypes(self::WITHOUT_TRANSFER);
    }

    /**
     * Un `enum` de Laravel no es un tipo propio: es un `varchar` con una
     * restricción `CHECK` que enumera los valores. PostgreSQL no sabe cambiarla
     * con un `ALTER COLUMN`, así que hay que reemplazar la restricción; el
     * resto de motores sí admiten redefinir la columna entera.
     *
     * @param  array<int, string>  $types
     */
    private function rewriteEntryTypes(array $types): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            Schema::table('app_entries', function (Blueprint $table) use ($types) {
                $table->enum('entry_type', $types)->default('purchase')->change();
            });

            return;
        }

        $values = implode(', ', array_map(
            static fn (string $type): string => "'{$type}'",
            $types,
        ));

        DB::statement('ALTER TABLE app_entries DROP CONSTRAINT IF EXISTS app_entries_entry_type_check');

        DB::statement(
            "ALTER TABLE app_entries ADD CONSTRAINT app_entries_entry_type_check CHECK (entry_type IN ({$values}))"
        );
    }
};
