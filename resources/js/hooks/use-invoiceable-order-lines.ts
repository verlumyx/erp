import { useCallback, useEffect, useRef, useState } from 'react';

/** Una línea del pedido con saldo por facturar, tal como la manda el backend. */
export interface InvoiceableOrderLine {
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
    /** Lo que ya se facturó de esa línea. */
    invoiced_quantity: string;
    /** Lo que queda: `quantity - invoiced_quantity`. */
    pending_quantity: string;
    unit_price: string;
    discount_percent: string;
    tax_id: string | null;
    tax_percent: string;
    withholding_percent: string;
    notes: string | null;
}

/**
 * Las líneas por facturar de la orden que se acaba de elegir.
 *
 * Es la segunda ida al servidor que el select no hace: el `lookup` trae las
 * órdenes, y esto trae el saldo por facturar de la que se eligió. Va aparte
 * porque ese saldo solo interesa de una orden, no de las veinte que ofrece el
 * menú.
 *
 * `fetchLines` se llama desde el propio manejador del select, no desde un
 * `useEffect`: el formulario necesita las líneas en el mismo paso en que copia
 * la cabecera. Un contador descarta la respuesta de una orden que el usuario ya
 * cambió, y el `AbortController` corta la petición en vuelo.
 */
export function useInvoiceableOrderLines(urlFor: (orderId: string) => string) {
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
        async (orderId: string): Promise<InvoiceableOrderLine[]> => {
            const request = ++latest.current;

            inFlight.current?.abort();

            const abort = new AbortController();
            inFlight.current = abort;

            setLoading(true);
            setFailed(false);

            try {
                const response = await fetch(buildUrl.current(orderId), {
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
                    data: InvoiceableOrderLine[];
                };

                /* Si el usuario ya eligió otra orden, esta respuesta llegó tarde. */
                if (request !== latest.current) {
                    return [];
                }

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

    return { loading, failed, fetchLines };
}
