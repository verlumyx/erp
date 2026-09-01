import { useForm, usePage } from '@inertiajs/react';
import type { AjaxOption } from '@/components/select2-ajax';
import { useConfiguration } from '@/hooks/use-configuration';
import { useRemoteOption } from '@/hooks/use-remote-option';
import { useTodayRates } from '@/hooks/use-today-rates';
import { generateUUID } from '@/lib/utils';
import clientCollections from '@/routes/client-collections';
import clients from '@/routes/clients';
import salesInvoices from '@/routes/sales-invoices';
import type {
    CheckStatus,
    ClientCollection,
    ClientCollectionMethod,
    ClientCollectionOriginType,
    ClientOptionMeta,
    SalesInvoiceOptionMeta,
} from '../types/ClientCollection';
import { useClientOpenInvoices } from './useClientOpenInvoices';

interface UseClientCollectionFormProps {
    mode: 'create' | 'edit';
    initialData?: ClientCollection;
    onSuccess?: () => void;
}

/** Una fila del reparto tal como se captura: qué factura y por cuánto. */
export interface ClientCollectionApplicationRow {
    sales_invoice_id: string;
    applied_amount: number;
}

interface ClientCollectionFormData {
    id: string;
    client_id: string;
    origin_type: ClientCollectionOriginType;
    origin_id: string;
    collection_date: string;
    payment_method: ClientCollectionMethod;
    reference: string;
    bank_account: string;
    collected_by: string;
    currency: string;
    /**
     * Corrección manual de la tasa. Vacío —el caso normal— hace que la resuelva
     * el backend con el catálogo a la fecha del cobro.
     */
    exchange_rate: string;
    amount: number;
    withholding_amount: number;
    check_number: string;
    check_date: string;
    check_status: CheckStatus | '';
    notes: string;
    applications: ClientCollectionApplicationRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/** Los dos indicadores del cliente que la pantalla muestra junto al origen. */
export interface ClientBalances {
    /** Suma de lo que deben sus facturas abiertas. */
    receivable: number;
    /**
     * Lo aplicable sin recibir dinero: sus anticipos disponibles. Las notas de
     * crédito se sumarán aquí cuando el módulo `NCC` las lleve.
     */
    credit: number;
}

export interface ClientCollectionTotals {
    /** Suma del reparto entre facturas. */
    applied: number;
    /** Lo que el cobro puede saldar: lo recibido más lo retenido. */
    capacity: number;
    /** Excedente sin aplicar. Al confirmar se vuelve un anticipo. */
    unapplied: number;
}

function round2(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

/**
 * El cliente del cobro que se edita, con la etiqueta que trae su Resource: así
 * el select lo muestra desde el primer render.
 */
function clientSeed(collection?: ClientCollection): AjaxOption | null {
    if (!collection?.client_id) {
        return null;
    }

    const name = collection.client_name ?? '';

    return {
        value: collection.client_id,
        label: collection.client_code
            ? `${collection.client_code} — ${name}`
            : name,
    };
}

/** La factura de origen del cobro que se edita, si nació de una. */
function originInvoiceSeed(collection?: ClientCollection): AjaxOption | null {
    if (collection?.origin_type !== 'invoice' || !collection.origin_id) {
        return null;
    }

    const applied = (collection.applications ?? []).find(
        (application) => application.sales_invoice_id === collection.origin_id,
    );

    return {
        value: collection.origin_id,
        label: applied?.sales_invoice_code ?? 'Factura de venta',
    };
}

/** El reparto guardado, sin las filas que ya se revirtieron. */
function applicationRows(
    collection?: ClientCollection,
): ClientCollectionApplicationRow[] {
    return (collection?.applications ?? [])
        .filter((application) => application.status === 'active')
        .map((application) => ({
            sales_invoice_id: application.sales_invoice_id,
            applied_amount: Number(application.applied_amount),
        }));
}

export function useClientCollectionForm({
    mode,
    initialData,
    onSuccess,
}: UseClientCollectionFormProps) {
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

    /** Y las facturas contra el suyo, acotadas a las que siguen debiendo. */
    const originInvoice = useRemoteOption({
        url: salesInvoices.lookup(companyId).url,
        seed: originInvoiceSeed(initialData),
        hydrate: true,
    });

    const initialCurrency =
        initialData?.currency ?? configuration?.base_currency ?? '';

    /**
     * Con la corrección permitida el campo nace con la tasa del catálogo a la
     * vista, no vacío: el usuario ve con qué se va a valorar el cobro antes de
     * guardarlo. Prohibida la corrección, viaja vacía y la resuelve el backend.
     */
    const catalogRate = (currency: string): string => {
        if (configuration?.allows_rate_override !== 'yes') {
            return '';
        }

        const rate = todayRates[currency];

        return rate === undefined ? '' : String(rate);
    };

    const { data, setData, post, put, transform, processing, errors, reset } =
        useForm<ClientCollectionFormData>({
            id: initialData?.id ?? generateUUID(),
            client_id: initialData?.client_id ?? '',
            origin_type: initialData?.origin_type ?? 'client',
            origin_id: initialData?.origin_id ?? '',
            collection_date:
                initialData?.collection_date ??
                new Date().toISOString().slice(0, 10),
            payment_method: initialData?.payment_method ?? 'cash',
            reference: initialData?.reference ?? '',
            bank_account: initialData?.bank_account ?? '',
            collected_by: initialData?.collected_by ?? '',
            /** Un cobro nace en la moneda en la que la empresa lleva sus cifras. */
            currency: initialCurrency,
            exchange_rate: catalogRate(initialCurrency),
            amount: Number(initialData?.amount ?? 0),
            withholding_amount: Number(initialData?.withholding_amount ?? 0),
            check_number: initialData?.check_number ?? '',
            check_date: initialData?.check_date ?? '',
            check_status: initialData?.check_status ?? '',
            notes: initialData?.notes ?? '',
            applications: applicationRows(initialData),
        });

    /** El reparto se arma sobre las facturas abiertas del cliente elegido. */
    const { invoices, loading: loadingInvoices } = useClientOpenInvoices(
        companyId,
        data.client_id,
    );

    /**
     * Cambiar la moneda del cobro trae la tasa del catálogo de esa moneda. El
     * monto ya capturado no se toca: es el que entró en caja.
     */
    const selectCurrency = (currency: string) =>
        setData((current) => ({
            ...current,
            currency,
            exchange_rate: catalogRate(currency),
        }));

    /**
     * Elegir el cliente descarta el reparto anterior: era de las facturas de
     * otro, y un cobro no cruza clientes.
     */
    const selectClient = (option: AjaxOption | null) => {
        client.select(option);

        setData((current) => ({
            ...current,
            client_id: option?.value ?? '',
            applications: [],
        }));
    };

    /**
     * Elegir la factura de origen fija el cliente y precarga esa factura en el
     * reparto por lo que debe; el usuario puede sumarle otras del mismo.
     */
    const selectOriginInvoice = (option: AjaxOption | null) => {
        originInvoice.select(option);

        const meta = (option?.meta ?? {}) as Partial<SalesInvoiceOptionMeta>;

        setData((current) => ({
            ...current,
            origin_id: option?.value ?? '',
            client_id: meta.client_id ?? '',
            applications: option
                ? [
                      {
                          sales_invoice_id: option.value,
                          applied_amount: Number(meta.balance ?? 0),
                      },
                  ]
                : [],
        }));

        if (meta.currency) {
            selectCurrency(meta.currency);
        }
    };

    /**
     * Cambiar de dónde arranca el cobro vacía lo que dependía del origen
     * anterior: el cliente, la factura y el reparto.
     */
    const selectOriginType = (originType: ClientCollectionOriginType) => {
        client.select(null);
        originInvoice.select(null);

        setData((current) => ({
            ...current,
            origin_type: originType,
            origin_id: '',
            client_id: '',
            applications: [],
        }));
    };

    /**
     * Cambiar de forma de cobro limpia lo que solo tiene sentido con cheque:
     * el backend lo descarta igual, y dejarlo a la vista confunde.
     */
    const selectPaymentMethod = (method: ClientCollectionMethod) =>
        setData((current) =>
            method === 'check'
                ? { ...current, payment_method: method }
                : {
                      ...current,
                      payment_method: method,
                      check_number: '',
                      check_date: '',
                      check_status: '',
                  },
        );

    /** Lo que el reparto ya destina a una factura. */
    const appliedTo = (invoiceId: string): number =>
        data.applications.find((row) => row.sales_invoice_id === invoiceId)
            ?.applied_amount ?? 0;

    /**
     * Escribir cuánto se le abona a una factura. Un cero la saca del reparto:
     * una fila en cero no aporta nada y el backend la rechaza.
     */
    const applyToInvoice = (invoiceId: string, amount: number) =>
        setData(
            'applications',
            amount > 0
                ? [
                      ...data.applications.filter(
                          (row) => row.sales_invoice_id !== invoiceId,
                      ),
                      {
                          sales_invoice_id: invoiceId,
                          applied_amount: amount,
                      },
                  ]
                : data.applications.filter(
                      (row) => row.sales_invoice_id !== invoiceId,
                  ),
        );

    /**
     * Reparte lo que queda del cobro entre las facturas abiertas, de la más
     * vieja a la más nueva: es como se salda una cuenta por cobrar.
     */
    const distributeRemaining = () => {
        let left = round2(data.amount + data.withholding_amount);

        setData(
            'applications',
            invoices.reduce<ClientCollectionApplicationRow[]>(
                (rows, invoice) => {
                    const amount = Math.min(left, Number(invoice.balance));

                    if (amount <= 0) {
                        return rows;
                    }

                    left = round2(left - amount);

                    return [
                        ...rows,
                        {
                            sales_invoice_id: invoice.id,
                            applied_amount: round2(amount),
                        },
                    ];
                },
                [],
            ),
        );
    };

    const balances: ClientBalances = (() => {
        const meta = (client.optionOf(data.client_id)?.meta ??
            {}) as Partial<ClientOptionMeta>;

        return {
            receivable: Number(meta.current_balance ?? 0),
            credit: Number(meta.advance_balance ?? 0),
        };
    })();

    const applied = round2(
        data.applications.reduce((total, row) => total + row.applied_amount, 0),
    );

    const capacity = round2(data.amount + data.withholding_amount);

    const totals: ClientCollectionTotals = {
        applied,
        capacity,
        unapplied: round2(capacity - applied),
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        /**
         * El campo enseña la tasa del catálogo, pero solo viaja como corrección
         * si el usuario escribió otra: así un cobro con fecha anterior se sigue
         * valorando con la tasa de su día y no con la de hoy.
         */
        transform((payload) => ({
            ...payload,
            exchange_rate:
                payload.exchange_rate === catalogRate(payload.currency)
                    ? ''
                    : payload.exchange_rate,
        }));

        if (mode === 'create') {
            post(clientCollections.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                clientCollections.update({
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
        totals,
        balances,
        invoices,
        loadingInvoices,
        clientLookupUrl: client.url,
        clientOption: client.optionOf(data.client_id),
        selectClient,
        originInvoiceLookupUrl: originInvoice.url,
        originInvoiceOption: originInvoice.optionOf(data.origin_id),
        selectOriginInvoice,
        selectOriginType,
        selectPaymentMethod,
        selectCurrency,
        appliedTo,
        applyToInvoice,
        distributeRemaining,
    };
}
