import { useCallback, useEffect, useRef, useState } from 'react';

/** Una línea de la orden con saldo, tal como la manda el backend. */
export interface PendingOrderLine {
    id: string;
    line_number: number;
    item_id: string;
    /** En compras el artículo se reconoce por su código; en ventas por su sku. */
    item_code?: string | null;
    item_sku?: string | null;
    item_name: string | null;
    measurement_unit_id: string;
    measurement_unit_name: string | null;
    /** Lo pedido. */
    quantity: string;
    /** Los dos avances de la línea; cuál falta lo dice el endpoint que se llamó. */
    received_quantity?: string;
    dispatched_quantity?: string;
    invoiced_quantity: string;
    /** Lo que queda, ya resuelto contra el avance que se preguntó. */
    pending_quantity: string;
    unit_price: string;
    discount_percent: string;
    tax_id: string | null;
    tax_percent: string;
    withholding_percent: string;
    notes: string | null;
}

interface UsePendingOrderLinesProps {
    /**
     * Cómo se arma la URL del saldo para una orden. Los `ids` son las líneas
     * que el documento ya tenía atadas y que vuelven aunque ya no deban nada.
     */
    urlFor: (orderId: string, ids: string) => string;
    /**
     * La orden que el documento ya traía al abrirse. Se pide sola para que la
     * pantalla sepa desde el primer render cuánto quedaba en cada línea, sin
     * tocar las líneas ya guardadas.
     */
    initialOrderId?: string;
    /** Las líneas de la orden que ese documento ya tenía atadas. */
    initialLineIds?: string[];
}

/**
 * El saldo de la orden que se acaba de elegir.
 *
 * Es la segunda ida al servidor que el select no hace: el `lookup` trae las
 * órdenes, y esto trae lo que le queda a la que se eligió. Va aparte porque ese
 * saldo solo interesa de una orden, no de las veinte que ofrece el menú.
 *
 * `fetchLines` se llama desde el propio manejador del select, no desde un
 * `useEffect`: el formulario necesita las líneas en el mismo paso en que copia
 * la cabecera. Un contador descarta la respuesta de una orden que el usuario ya
 * cambió, y el `AbortController` corta la petición en vuelo.
 */
export function usePendingOrderLines({
    urlFor,
    initialOrderId = '',
    initialLineIds = [],
}: UsePendingOrderLinesProps) {
    /** Lo último que trajo el servidor: de aquí sale «quedan N» por línea. */
    const [lines, setLines] = useState<PendingOrderLine[]>([]);
    const [loading, setLoading] = useState(false);
    /** Verdadero cuando la última petición no llegó: la pantalla lo avisa. */
    const [failed, setFailed] = useState(false);

    const latest = useRef(0);
    const inFlight = useRef<AbortController | null>(null);

    /*
     * El que llama arma la URL con una función nueva en cada render; guardarla
     * en una referencia deja `fetchLines` estable.
     */
    const buildUrl = useRef(urlFor);
    buildUrl.current = urlFor;

    /** Una petición viva no sobrevive a la pantalla que la pidió. */
    useEffect(() => () => inFlight.current?.abort(), []);

    const fetchLines = useCallback(
        async (orderId: string, ids = ''): Promise<PendingOrderLine[]> => {
            const request = ++latest.current;

            inFlight.current?.abort();

            const abort = new AbortController();
            inFlight.current = abort;

            setLoading(true);
            setFailed(false);

            try {
                const response = await fetch(buildUrl.current(orderId, ids), {
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
                    data: PendingOrderLine[];
                };

                /* Si el usuario ya eligió otra orden, esta respuesta llegó tarde. */
                if (request !== latest.current) {
                    return [];
                }

                setLines(payload.data);

                return payload.data;
            } catch {
                /* Abortada: o llegó otra orden, o la pantalla se fue. No es un fallo. */
                if (!abort.signal.aborted && request === latest.current) {
                    setFailed(true);
                }

                return [];
            } finally {
                if (request === latest.current) {
                    setLoading(false);
                }
            }
        },
        [],
    );

    /** Sin orden no hay saldo que mostrar. */
    const clear = useCallback(() => {
        latest.current += 1;
        inFlight.current?.abort();
        setLines([]);
        setFailed(false);
    }, []);

    /** El documento que se edita ya venía de una orden: se pide su saldo. */
    const hydrated = useRef(false);

    /* Sin serializar, el efecto se repetiría con cada arreglo nuevo. */
    const seedIds = initialLineIds.filter((id) => id !== '').join(',');

    useEffect(() => {
        if (hydrated.current || initialOrderId === '') {
            return;
        }

        hydrated.current = true;

        void fetchLines(initialOrderId, seedIds);
    }, [initialOrderId, seedIds, fetchLines]);

    return { lines, loading, failed, fetchLines, clear };
}
