import { useForm, usePage } from '@inertiajs/react';
import type { AjaxOption } from '@/components/select2-ajax';
import { useConfiguration } from '@/hooks/use-configuration';
import { useRemoteOption } from '@/hooks/use-remote-option';
import { useTodayRates } from '@/hooks/use-today-rates';
import { generateUUID } from '@/lib/utils';
import clientAdvances from '@/routes/client-advances';
import clients from '@/routes/clients';
import salesOrders from '@/routes/sales-orders';
import type {
    ClientAdvance,
    ClientAdvancePaymentMethod,
    ClientOptionMeta,
    SalesOrderOptionMeta,
} from '../types/ClientAdvance';

interface UseClientAdvanceFormProps {
    mode: 'create' | 'edit';
    initialData?: ClientAdvance;
    onSuccess?: () => void;
}

interface ClientAdvanceFormData {
    id: string;
    client_id: string;
    sales_order_id: string;
    advance_date: string;
    payment_method: ClientAdvancePaymentMethod;
    reference: string;
    bank_account: string;
    currency: string;
    /**
     * Corrección manual de la tasa. Vacío —el caso normal— hace que la resuelva
     * el backend con el catálogo a la fecha del anticipo.
     */
    exchange_rate: string;
    amount: number;
    notes: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/** Los dos indicadores del cliente que la pantalla muestra al elegirlo. */
export interface ClientBalances {
    /** Suma de lo que deben sus facturas abiertas. */
    receivable: number;
    /** Lo que ya adelantó y todavía no se ha aplicado. */
    credit: number;
}

/**
 * El cliente del anticipo que se edita, con la etiqueta que trae su Resource:
 * así el select lo muestra desde el primer render.
 */
function clientSeed(advance?: ClientAdvance): AjaxOption | null {
    if (!advance?.client_id) {
        return null;
    }

    const name = advance.client_name ?? '';

    return {
        value: advance.client_id,
        label: advance.client_code ? `${advance.client_code} — ${name}` : name,
    };
}

/** El pedido que motiva el anticipo que se edita, si nació de uno. */
function salesOrderSeed(advance?: ClientAdvance): AjaxOption | null {
    if (!advance?.sales_order_id) {
        return null;
    }

    return {
        value: advance.sales_order_id,
        label: advance.sales_order_code ?? 'Pedido de venta',
    };
}

export function useClientAdvanceForm({
    mode,
    initialData,
    onSuccess,
}: UseClientAdvanceFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const configuration = useConfiguration();
    const todayRates = useTodayRates();

    /** El padrón de clientes se busca contra su endpoint de opciones. */
    const client = useRemoteOption({
        url: clients.lookup(companyId).url,
        seed: clientSeed(initialData),
        /** De su `meta` salen los dos indicadores, así que hay que hidratarlo. */
        hydrate: true,
    });

    /** Y los pedidos contra el suyo, acotados a los del cliente elegido. */
    const salesOrder = useRemoteOption({
        url: salesOrders.lookup(companyId).url,
        seed: salesOrderSeed(initialData),
    });

    const initialCurrency =
        initialData?.currency ?? configuration?.base_currency ?? '';

    /**
     * Con la corrección permitida el campo nace con la tasa del catálogo a la
     * vista, no vacío: el usuario ve con qué se va a valorar el anticipo antes
     * de guardarlo. Prohibida la corrección, viaja vacía y la resuelve el
     * backend.
     */
    const catalogRate = (currency: string): string => {
        if (configuration?.allows_rate_override !== 'yes') {
            return '';
        }

        const rate = todayRates[currency];

        return rate === undefined ? '' : String(rate);
    };

    const { data, setData, post, put, transform, processing, errors, reset } =
        useForm<ClientAdvanceFormData>({
            id: initialData?.id ?? generateUUID(),
            client_id: initialData?.client_id ?? '',
            sales_order_id: initialData?.sales_order_id ?? '',
            advance_date:
                initialData?.advance_date ??
                new Date().toISOString().slice(0, 10),
            payment_method: initialData?.payment_method ?? 'transfer',
            reference: initialData?.reference ?? '',
            bank_account: initialData?.bank_account ?? '',
            /** Un anticipo nace en la moneda en la que la empresa lleva sus cifras. */
            currency: initialCurrency,
            exchange_rate: catalogRate(initialCurrency),
            amount: Number(initialData?.amount ?? 0),
            notes: initialData?.notes ?? '',
        });

    /**
     * Cambiar la moneda trae la tasa del catálogo de esa moneda. El monto ya
     * capturado no se toca: es el que el cliente entregó.
     */
    const selectCurrency = (currency: string) =>
        setData((current) => ({
            ...current,
            currency,
            exchange_rate: catalogRate(currency),
        }));

    /**
     * Elegir el cliente descarta el pedido anterior: era de otro cliente, y un
     * anticipo no cruza clientes.
     */
    const selectClient = (option: AjaxOption | null) => {
        client.select(option);
        salesOrder.select(null);

        setData((current) => ({
            ...current,
            client_id: option?.value ?? '',
            sales_order_id: '',
        }));
    };

    /** Elegir el pedido arrastra su moneda: el anticipo se recibe en la misma. */
    const selectSalesOrder = (option: AjaxOption | null) => {
        salesOrder.select(option);

        const meta = (option?.meta ?? {}) as Partial<SalesOrderOptionMeta>;

        setData((current) => ({
            ...current,
            sales_order_id: option?.value ?? '',
            client_id: meta.client_id ?? current.client_id,
        }));

        if (meta.currency) {
            selectCurrency(meta.currency);
        }
    };

    const balances: ClientBalances = (() => {
        const meta = (client.optionOf(data.client_id)?.meta ??
            {}) as Partial<ClientOptionMeta>;

        return {
            receivable: Number(meta.current_balance ?? 0),
            credit: Number(meta.advance_balance ?? 0),
        };
    })();

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        /**
         * El campo enseña la tasa del catálogo, pero solo viaja como corrección
         * si el usuario escribió otra: así un anticipo con fecha anterior se
         * sigue valorando con la tasa de su día y no con la de hoy.
         */
        transform((payload) => ({
            ...payload,
            exchange_rate:
                payload.exchange_rate === catalogRate(payload.currency)
                    ? ''
                    : payload.exchange_rate,
        }));

        if (mode === 'create') {
            post(clientAdvances.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                clientAdvances.update({
                    company: companyId,
                    id: initialData.id,
                }).url,
                { onSuccess },
            );
        }
    };

    return {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        reset,
        mode,
        balances,
        clientLookupUrl: client.url,
        clientOption: client.optionOf(data.client_id),
        selectClient,
        salesOrderLookupUrl: salesOrder.url,
        salesOrderOption: salesOrder.optionOf(data.sales_order_id),
        selectSalesOrder,
        selectCurrency,
    };
}
