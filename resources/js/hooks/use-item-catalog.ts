import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import items from '@/routes/items';

export interface ItemCatalogUnit {
    measurement_unit_id: string;
    name: string;
    is_base: 'yes' | 'no';
    conversion_factor: string;
}

export interface ItemCatalogPrice {
    price_list_id: string;
    price: string;
    currency: string;
}

/**
 * Lo que una pantalla sabe de un artículo con solo haberlo elegido.
 *
 * Va como `type` y no como `interface` a propósito: así viaja tal cual en el
 * `meta` de la opción, que es un `Record<string, unknown>`.
 */
export type ItemCatalogEntry = {
    id: string;
    code: string;
    sku: string | null;
    name: string;
    min_price: string;
    standard_cost: string;
    /** Impuesto con el que el artículo se vende y con el que se compra. */
    sale_tax_id: string | null;
    purchase_tax_id: string | null;
    units: ItemCatalogUnit[];
    prices: ItemCatalogPrice[];
    /**
     * `false` mientras la entrada solo tenga el nombre que trajo el documento.
     * Sus unidades y precios vacíos son «todavía no se sabe», no «no tiene»:
     * quien revalúe una línea debe esperar a que sea `true`.
     */
    hydrated: boolean;
};

/** Artículo ya elegido en un documento que se está editando. */
export interface ItemCatalogSeed {
    id: string;
    code?: string | null;
    sku?: string | null;
    name?: string | null;
}

interface UseItemCatalogProps {
    companyId: string;
    /** Artículos de las líneas que ya existen. Vacío al crear. */
    seed?: ItemCatalogSeed[];
    /** Cómo nombra el módulo a un artículo: por sku en ventas, por código en compras. */
    formatLabel: (entry: ItemCatalogEntry) => string;
}

/** Tope de `per_page` del endpoint de opciones. */
const MAX_IDS_PER_REQUEST = 50;

function entryFromOption(option: AjaxOption): ItemCatalogEntry {
    const meta = (option.meta ?? {}) as Partial<ItemCatalogEntry>;

    return {
        id: option.value,
        code: meta.code ?? '',
        sku: meta.sku ?? null,
        name: meta.name ?? option.label,
        min_price: meta.min_price ?? '0',
        standard_cost: meta.standard_cost ?? '0',
        sale_tax_id: meta.sale_tax_id ?? null,
        purchase_tax_id: meta.purchase_tax_id ?? null,
        units: meta.units ?? [],
        prices: meta.prices ?? [],
        hydrated: true,
    };
}

/**
 * Una entrada provisional con lo que el Resource del documento ya trae: sirve
 * para que la línea muestre su artículo desde el primer render, sin esperar a
 * la hidratación. Sus unidades y precios llegan después.
 */
function entryFromSeed(seed: ItemCatalogSeed): ItemCatalogEntry {
    return {
        id: seed.id,
        code: seed.code ?? '',
        sku: seed.sku ?? null,
        name: seed.name ?? '',
        min_price: '0',
        standard_cost: '0',
        sale_tax_id: null,
        purchase_tax_id: null,
        units: [],
        prices: [],
        hydrated: false,
    };
}

function chunk<T>(values: T[], size: number): T[][] {
    const chunks: T[][] = [];

    for (let index = 0; index < values.length; index += size) {
        chunks.push(values.slice(index, index + size));
    }

    return chunks;
}

/**
 * Memoria de los artículos que una pantalla ha visto.
 *
 * Desde que el catálogo dejó de viajar en las props, un formulario solo conoce
 * los artículos que el usuario ha elegido: los que vienen del documento que se
 * edita (hidratados por id al montar) y los que va escogiendo en el select
 * remoto. De aquí salen la unidad base, el precio de lista y el costo estándar
 * de cada línea.
 */
export function useItemCatalog({
    companyId,
    seed = [],
    formatLabel,
}: UseItemCatalogProps) {
    const [entries, setEntries] = useState<Record<string, ItemCatalogEntry>>(
        () =>
            Object.fromEntries(
                seed
                    .filter((candidate) => candidate.id !== '')
                    .map((candidate) => [
                        candidate.id,
                        entryFromSeed(candidate),
                    ]),
            ),
    );

    const url = items.lookup(companyId).url;

    /** Los ids ya elegidos: sin serializar, el efecto se repetiría en cada render. */
    const seedIds = seed
        .map((candidate) => candidate.id)
        .filter((id) => id !== '')
        .join(',');

    /**
     * Ids ya pedidos al servidor. Es un conjunto y no un booleano porque la
     * semilla crece: al facturar un pedido, sus artículos entran de golpe y
     * hay que hidratar solo los que aún no se conocen.
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

                const hydratedEntries = pages
                    .flatMap((payload) => payload.data)
                    .map(entryFromOption);

                setEntries((previous) => ({
                    ...previous,
                    ...Object.fromEntries(
                        hydratedEntries.map((entry) => [entry.id, entry]),
                    ),
                }));
            } catch {
                /*
                 * Sin hidratar, la línea conserva el nombre que trajo el
                 * documento; lo que se pierde es su unidad y su precio, que el
                 * usuario puede recapturar reeligiendo el artículo.
                 */
                missing.forEach((id) => requested.current.delete(id));
            }
        })();

        return () => abort.abort();
    }, [url, seedIds]);

    /** El artículo de una línea, si la pantalla ya lo conoce. */
    const itemOf = useCallback(
        (itemId: string): ItemCatalogEntry | undefined => entries[itemId],
        [entries],
    );

    /** Guarda el artículo recién elegido y devuelve lo que la línea necesita. */
    const remember = useCallback((option: AjaxOption): ItemCatalogEntry => {
        const entry = entryFromOption(option);

        setEntries((previous) => ({ ...previous, [entry.id]: entry }));

        return entry;
    }, []);

    /** El valor que espera `Select2Ajax` para el artículo de una línea. */
    const optionOf = useCallback(
        (itemId: string): AjaxOption | null => {
            const entry = entries[itemId];

            if (!entry) {
                return null;
            }

            return { value: entry.id, label: formatLabel(entry), meta: entry };
        },
        [entries, formatLabel],
    );

    /** El mismo formato, para las opciones que llegan del servidor. */
    const labelOf = useCallback(
        (option: AjaxOption): string => formatLabel(entryFromOption(option)),
        [formatLabel],
    );

    return useMemo(
        () => ({ url, itemOf, remember, optionOf, labelOf }),
        [url, itemOf, remember, optionOf, labelOf],
    );
}

export type ItemCatalog = ReturnType<typeof useItemCatalog>;
