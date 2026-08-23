import { useEffect, useRef, useState } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import purchaseInvoices from '@/routes/purchase-invoices';
import type { PurchaseInvoiceOptionMeta } from '../types/SupplierPayment';

/** Una factura abierta del proveedor, tal como la ofrece el reparto. */
export interface OpenInvoice {
    id: string;
    code: string;
    supplierInvoiceNumber: string;
    dueDate: string | null;
    currency: string;
    exchangeRate: string;
    total: string;
    balance: string;
}

/** Cuántas facturas abiertas se traen de una sola vez. */
const MAX_INVOICES = 50;

function toOpenInvoice(option: AjaxOption): OpenInvoice {
    const meta = (option.meta ?? {}) as Partial<PurchaseInvoiceOptionMeta>;

    return {
        id: option.value,
        code: meta.code ?? option.label,
        supplierInvoiceNumber: meta.supplier_invoice_number ?? '',
        dueDate: meta.due_date ?? null,
        currency: meta.currency ?? '',
        exchangeRate: meta.exchange_rate ?? '0',
        total: meta.total ?? '0',
        balance: meta.balance ?? '0',
    };
}

/**
 * Las facturas del proveedor que todavía deben algo.
 *
 * No es un select: el reparto las muestra todas para repartir el monto entre
 * ellas, así que se piden contra el mismo endpoint de opciones que usa
 * `Select2Ajax` pero de una sola vez.
 *
 * Sin proveedor elegido no hay nada que pedir, y cambiar de proveedor descarta
 * lo que trajo el anterior.
 */
export function useSupplierOpenInvoices(companyId: string, supplierId: string) {
    const [invoices, setInvoices] = useState<OpenInvoice[]>([]);
    const [loading, setLoading] = useState(false);
    const url = purchaseInvoices.lookup(companyId).url;
    const latest = useRef(0);

    useEffect(() => {
        if (supplierId === '') {
            setInvoices([]);

            return;
        }

        const request = ++latest.current;
        const abort = new AbortController();

        setLoading(true);

        void (async () => {
            try {
                const response = await fetch(
                    `${url}?supplier_id=${supplierId}&open=yes&per_page=${MAX_INVOICES}`,
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

                /* Si el usuario ya cambió de proveedor, esta respuesta llegó tarde. */
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
    }, [url, supplierId]);

    return { invoices, loading };
}
