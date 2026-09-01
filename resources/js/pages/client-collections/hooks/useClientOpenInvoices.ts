import { useEffect, useRef, useState } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import salesInvoices from '@/routes/sales-invoices';
import type { SalesInvoiceOptionMeta } from '../types/ClientCollection';

/** Una factura abierta del cliente, tal como la ofrece el reparto. */
export interface OpenInvoice {
    id: string;
    code: string;
    invoiceNumber: string;
    dueDate: string | null;
    currency: string;
    exchangeRate: string;
    total: string;
    balance: string;
}

/** Cuántas facturas abiertas se traen de una sola vez. */
const MAX_INVOICES = 50;

function toOpenInvoice(option: AjaxOption): OpenInvoice {
    const meta = (option.meta ?? {}) as Partial<SalesInvoiceOptionMeta>;

    return {
        id: option.value,
        code: meta.code ?? option.label,
        invoiceNumber: meta.invoice_number ?? '',
        dueDate: meta.due_date ?? null,
        currency: meta.currency ?? '',
        exchangeRate: meta.exchange_rate ?? '0',
        total: meta.total ?? '0',
        balance: meta.balance ?? '0',
    };
}

/**
 * Las facturas del cliente que todavía deben algo.
 *
 * No es un select: el reparto las muestra todas para repartir el monto entre
 * ellas, así que se piden contra el mismo endpoint de opciones que usa
 * `Select2Ajax` pero de una sola vez.
 *
 * Sin cliente elegido no hay nada que pedir, y cambiar de cliente descarta lo
 * que trajo el anterior.
 */
export function useClientOpenInvoices(companyId: string, clientId: string) {
    const [invoices, setInvoices] = useState<OpenInvoice[]>([]);
    const [loading, setLoading] = useState(false);
    const url = salesInvoices.lookup(companyId).url;
    const latest = useRef(0);

    useEffect(() => {
        if (clientId === '') {
            setInvoices([]);

            return;
        }

        const request = ++latest.current;
        const abort = new AbortController();

        setLoading(true);

        void (async () => {
            try {
                const response = await fetch(
                    `${url}?client_id=${clientId}&open=yes&per_page=${MAX_INVOICES}`,
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

                const payload = (await response.json()) as {
                    data: AjaxOption[];
                };

                /* Si el usuario ya cambió de cliente, esta respuesta llegó tarde. */
                if (request === latest.current) {
                    setInvoices(payload.data.map(toOpenInvoice));
                }
            } catch {
                if (request === latest.current) {
                    setInvoices([]);
                }
            } finally {
                if (request === latest.current) {
                    setLoading(false);
                }
            }
        })();

        return () => abort.abort();
    }, [url, clientId]);

    return { invoices, loading };
}
