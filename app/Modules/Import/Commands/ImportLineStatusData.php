<?php

declare(strict_types=1);

namespace App\Modules\Import\Commands;

/**
 * Lo único que la pantalla puede decir de un ítem del expediente: si entra o no
 * en el reparto.
 *
 * Los ítems no se capturan —se derivan de las recepciones—, así que de la
 * sección solo viaja el estado de cada fila. Un `base_quantity` o un
 * `allocated_amount` enviados desde el cliente se ignoran.
 *
 * La fila se reconoce por su línea de entrada y no por su propio id: la línea
 * del expediente se vuelve a derivar en cada guardado, y la de la entrada es lo
 * único que sigue siendo la misma entre uno y otro.
 */
class ImportLineStatusData
{
    /**
     * Estados enviados, indexados por el id de la línea de entrada.
     *
     * @return array<string, string>
     */
    public static function map(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $statuses = [];

        foreach (array_filter($rows, 'is_array') as $row) {
            $entryLineId = (string) ($row['entry_line_id'] ?? '');

            if ($entryLineId === '') {
                continue;
            }

            $statuses[$entryLineId] = ($row['status'] ?? 'active') === 'inactive'
                ? 'inactive'
                : 'active';
        }

        return $statuses;
    }
}
