import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';

/** Un valor ya elegido en un documento que se está editando. */
export interface RemoteOptionSeed {
    id: string;
    label?: string | null;
}

interface UseRemoteOptionSetProps {
    /** URL del endpoint de opciones, p. ej. `itemLots.lookup(companyId).url`. */
    url: string;
    /** Valores de las líneas que ya existen. Vacío al crear. */
    seed?: RemoteOptionSeed[];
}

/** Tope de `per_page` de los endpoints de opciones. */
const MAX_IDS_PER_REQUEST = 50;

function chunk<T>(values: T[], size: number): T[][] {
    const chunks: T[][] = [];

    for (let index = 0; index < values.length; index += size) {
        chunks.push(values.slice(index, index + size));
    }

    return chunks;
}

/**
 * Memoria de **varios** valores elegidos en el mismo select remoto.
 *
 * `useRemoteOption` recuerda uno solo, que basta para un campo de cabecera. Un
 * documento con líneas necesita esto otro: cada línea elige su lote o su serie
 * contra el mismo endpoint, y la pantalla tiene que poder devolverle la opción
 * completa —`Select2Ajax` recibe `{ value, label, meta }`, nunca un id—.
 *
 * Es la misma idea de `useItemCatalog`, sin lo que allí es propio del artículo:
 * aquí una entrada es la opción tal cual la devolvió el servidor.
 */
export function useRemoteOptionSet({
    url,
    seed = [],
}: UseRemoteOptionSetProps) {
    const [options, setOptions] = useState<Record<string, AjaxOption>>(() =>
        Object.fromEntries(
            seed
                .filter((candidate) => candidate.id !== '')
                .map((candidate) => [
                    candidate.id,
                    { value: candidate.id, label: candidate.label ?? '' },
                ]),
        ),
    );

    /** Los ids ya elegidos: sin serializar, el efecto se repetiría en cada render. */
    const seedIds = seed
        .map((candidate) => candidate.id)
        .filter((id) => id !== '')
        .join(',');

    /**
     * Ids ya pedidos al servidor. Es un conjunto y no un booleano porque la
     * semilla crece: copiar las líneas de una factura mete lotes que la
     * pantalla nunca había visto.
     */
    const requested = useRef<Set<string>>(new Set());

    useEffect(() => {
        const missing = seedIds
            .split(',')
            .filter((id) => id !== '' && !requested.current.has(id));

        if (missing.length === 0) {
            return;
        }

        missing.forEach((id) => requested.current.add(id));

        const abort = new AbortController();

        void (async () => {
            try {
                const pages = await Promise.all(
                    chunk(missing, MAX_IDS_PER_REQUEST).map(async (ids) => {
                        const response = await fetch(
                            `${url}?ids=${ids.join(',')}&per_page=${ids.length}`,
                            {
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                signal: abort.signal,
                            },
                        );

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }

                        return (await response.json()) as {
                            data: AjaxOption[];
                        };
                    }),
                );

                const hydrated = pages.flatMap((payload) => payload.data);

                setOptions((previous) => ({
                    ...previous,
                    ...Object.fromEntries(
                        hydrated.map((option) => [option.value, option]),
                    ),
                }));
            } catch {
                /*
                 * Sin hidratar, la línea conserva la etiqueta que trajo el
                 * documento; lo que se pierde es su `meta`, que el usuario
                 * recupera reeligiendo el valor.
                 */
                missing.forEach((id) => requested.current.delete(id));
            }
        })();

        return () => abort.abort();
    }, [url, seedIds]);

    /** El valor que espera `Select2Ajax` para una línea. */
    const optionOf = useCallback(
        (id: string): AjaxOption | null => (id ? (options[id] ?? null) : null),
        [options],
    );

    /** Guarda el valor recién elegido, para que la línea sepa mostrarlo. */
    const remember = useCallback((option: AjaxOption): void => {
        setOptions((previous) => ({ ...previous, [option.value]: option }));
    }, []);

    return useMemo(
        () => ({ url, optionOf, remember }),
        [url, optionOf, remember],
    );
}

export type RemoteOptionSet = ReturnType<typeof useRemoteOptionSet>;
