import { useCallback, useEffect, useRef, useState } from 'react';
import adjustments from '@/routes/adjustments';

/** Lo que hay que saber de una línea para preguntar por su existencia. */
export interface AdjustmentStockKey {
    item_id: string;
    measurement_unit_id: string;
    location_id: string;
    lot_id: string;
}

/** Lo que el sistema responde de esa existencia. */
export interface AdjustmentStockEntry {
    system: number;
    average: number;
}

const EMPTY: AdjustmentStockEntry = { system: 0, average: 0 };

function keyOf(warehouseId: string, key: AdjustmentStockKey): string {
    return [
        warehouseId,
        key.item_id,
        key.measurement_unit_id,
        key.location_id,
        key.lot_id,
    ].join('|');
}

interface UseAdjustmentStockProps {
    companyId: string;
    warehouseId: string;
    /** Las claves vivas del formulario, en el orden de las líneas. */
    keys: AdjustmentStockKey[];
}

/**
 * Contra qué se compara lo que el usuario cuenta.
 *
 * La existencia del sistema no viaja en las props ni se captura: la pantalla la
 * pregunta por cada combinación de artículo, unidad, ubicación y lote que el
 * usuario va eligiendo, y la recuerda mientras la línea siga apuntando ahí. El
 * número que se guarda lo vuelve a resolver el backend, así que esto es solo lo
 * que se enseña mientras se cuenta.
 */
export function useAdjustmentStock({
    companyId,
    warehouseId,
    keys,
}: UseAdjustmentStockProps) {
    const [entries, setEntries] = useState<
        Record<string, AdjustmentStockEntry>
    >({});

    /** Claves ya pedidas al servidor, para no repetir la consulta en cada render. */
    const requested = useRef<Set<string>>(new Set());

    const url = adjustments.stock(companyId).url;

    /**
     * Sin serializar, el efecto se dispararía en cada render: `keys` es un
     * array nuevo cada vez aunque su contenido no cambie.
     */
    const signature = keys
        .filter((key) => key.item_id !== '' && key.measurement_unit_id !== '')
        .map((key) => keyOf(warehouseId, key))
        .join(',');

    useEffect(() => {
        if (warehouseId === '' || signature === '') {
            return;
        }

        const missing = [...new Set(signature.split(','))].filter(
            (candidate) => !requested.current.has(candidate),
        );

        if (missing.length === 0) {
            return;
        }

        missing.forEach((candidate) => requested.current.add(candidate));

        const abort = new AbortController();

        void (async () => {
            try {
                const resolved = await Promise.all(
                    missing.map(async (candidate) => {
                        const [warehouse, item, unit, location, lot] =
                            candidate.split('|');

                        const query = new URLSearchParams({
                            warehouse_id: warehouse,
                            item_id: item,
                            measurement_unit_id: unit,
                        });

                        if (location !== '') {
                            query.set('location_id', location);
                        }

                        if (lot !== '') {
                            query.set('lot_id', lot);
                        }

                        const response = await fetch(`${url}?${query}`, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            signal: abort.signal,
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }

                        const payload = (await response.json()) as {
                            system_quantity: string;
                            average_cost: string;
                        };

                        return [
                            candidate,
                            {
                                system: Number(payload.system_quantity),
                                average: Number(payload.average_cost),
                            },
                        ] as const;
                    }),
                );

                setEntries((previous) => ({
                    ...previous,
                    ...Object.fromEntries(resolved),
                }));
            } catch {
                /*
                 * Sin respuesta la línea se queda comparando contra cero, que es
                 * visible: el usuario ve una diferencia que no cuadra y vuelve a
                 * elegir el artículo para reintentarlo.
                 */
                missing.forEach((candidate) =>
                    requested.current.delete(candidate),
                );
            }
        })();

        return () => abort.abort();
    }, [url, warehouseId, signature]);

    const stockOf = useCallback(
        (key: AdjustmentStockKey): AdjustmentStockEntry =>
            entries[keyOf(warehouseId, key)] ?? EMPTY,
        [entries, warehouseId],
    );

    return { stockOf };
}
