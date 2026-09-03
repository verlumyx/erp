import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import { useConfiguration } from '@/hooks/use-configuration';
import {
    usePendingOrderLines,
    type PendingOrderLine,
} from '@/hooks/use-pending-order-lines';
import {
    useItemCatalog,
    type ItemCatalogEntry,
    type ItemCatalogSeed,
} from '@/hooks/use-item-catalog';
import { useRemoteOption } from '@/hooks/use-remote-option';
import { useTodayRates } from '@/hooks/use-today-rates';
import { convertAmount } from '@/lib/money';
import { generateUUID } from '@/lib/utils';
import clients from '@/routes/clients';
import salesInvoices from '@/routes/sales-invoices';
import salesOrders from '@/routes/sales-orders';
import type { TodayRates } from '@/types';
import { taxWithholdingPercent, type TaxOption } from '@/types/tax';
import type {
    ClientOptionMeta,
    SalesInvoice,
    SalesInvoiceOptions,
    SalesOrderOptionMeta,
    SaleType,
} from '../types/SalesInvoice';

interface UseSalesInvoiceFormProps {
    mode: 'create' | 'edit';
    options: SalesInvoiceOptions;
    initialData?: SalesInvoice;
    onSuccess?: () => void;
}

export interface SalesInvoiceLineRow {
    id: string;
    /** Línea del pedido de origen. Vacía en una factura directa. */
    sourceable_id: string;
    item_id: string;
    measurement_unit_id: string;
    quantity: number;
    unit_price: number;
    /**
     * Precio sugerido por la lista del cliente. No viaja al backend: la
     * factura no aplica lista de precio, solo la usa para proponer el precio.
     */
    list_price: number;
    discount_percent: number;
    /** Impuesto del catálogo. De él salen los dos porcentajes de abajo. */
    tax_id: string;
    tax_percent: number;
    withholding_percent: number;
    notes: string;
}

interface SalesInvoiceFormData {
    id: string;
    client_id: string;
    sourceable_type: string;
    sourceable_id: string;
    client_address_id: string;
    warehouse_id: string;
    salesperson_id: string;
    invoice_series: string;
    invoice_date: string;
    due_date: string;
    sale_type: SaleType;
    affects_inventory: 'yes' | 'no';
    freight_amount: number;
    currency: string;
    /**
     * Corrección manual de la tasa. Vacío —el caso normal— hace que la
     * resuelva el backend con el catálogo a la fecha de la factura.
     */
    exchange_rate: string;
    notes: string;
    lines: SalesInvoiceLineRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/** Importes de una línea. Es el mismo cálculo que hace el repositorio. */
export interface LineAmounts {
    gross: number;
    discountAmount: number;
    subtotal: number;
    taxAmount: number;
    withholdingAmount: number;
    total: number;
}

export function lineAmounts(line: SalesInvoiceLineRow): LineAmounts {
    const gross = round(line.quantity * line.unit_price, 2);
    const discountAmount = round((gross * line.discount_percent) / 100, 2);
    const subtotal = round(gross - discountAmount, 2);
    const taxAmount = round((subtotal * line.tax_percent) / 100, 2);

    return {
        gross,
        discountAmount,
        subtotal,
        taxAmount,
        /** La retención se practica sobre el impuesto, no sobre la base. */
        withholdingAmount: round(
            (taxAmount * line.withholding_percent) / 100,
            2,
        ),
        total: round(subtotal + taxAmount, 2),
    };
}

function round(value: number, decimals: number): number {
    const factor = 10 ** decimals;

    return Math.round((value + Number.EPSILON) * factor) / factor;
}

function emptyLine(): SalesInvoiceLineRow {
    return {
        id: generateUUID(),
        sourceable_id: '',
        item_id: '',
        measurement_unit_id: '',
        quantity: 1,
        unit_price: 0,
        list_price: 0,
        discount_percent: 0,
        tax_id: '',
        tax_percent: 0,
        withholding_percent: 0,
        notes: '',
    };
}

/**
 * Solo se editan las líneas activas: las inactivas se conservan en la base por
 * la política de no borrado, pero no vuelven al formulario.
 */
function lineRows(invoice?: SalesInvoice): SalesInvoiceLineRow[] {
    const rows = (invoice?.lines ?? [])
        .filter((line) => line.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((line) => ({
            id: line.id,
            sourceable_id: line.sourceable_id ?? '',
            item_id: line.item_id,
            measurement_unit_id: line.measurement_unit_id,
            quantity: Number(line.quantity),
            unit_price: Number(line.unit_price),
            /** Lo ya guardado es el precio pactado: no hay otro que sugerir. */
            list_price: Number(line.unit_price),
            discount_percent: Number(line.discount_percent),
            tax_id: line.tax_id ?? '',
            tax_percent: Number(line.tax_percent),
            withholding_percent: Number(line.withholding_percent),
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine()];
}

function defaultWarehouseId(options: SalesInvoiceOptions): string {
    const preferred = options.warehouses.find(
        (warehouse) => warehouse.is_default === 'yes',
    );

    return preferred?.id ?? options.warehouses[0]?.id ?? '';
}

/**
 * Precio del artículo en la lista del cliente, reexpresado en la moneda de la
 * factura.
 *
 * Es solo una sugerencia de la pantalla: la factura no guarda lista de precio
 * y el backend congela el precio que se envíe. Sin lista, sin precio
 * registrado o sin tasa para convertirlo cae en 0 y el usuario lo captura a
 * mano.
 */
function resolvePrice(
    item: ItemCatalogEntry | undefined,
    priceListId: string,
    currency: string,
    rates: TodayRates,
): number {
    if (!item || priceListId === '') {
        return 0;
    }

    const price = item.prices.find(
        (candidate) => candidate.price_list_id === priceListId,
    );

    if (!price) {
        return 0;
    }

    if (price.currency === currency) {
        return Number(price.price);
    }

    return (
        convertAmount(
            Number(price.price),
            rates[price.currency],
            rates[currency],
        ) ?? 0
    );
}

/**
 * La línea con un impuesto del catálogo aplicado: sus dos porcentajes salen de
 * ahí y ya no se capturan a mano. Sin impuesto, ambos vuelven a cero.
 */
function withTax(
    line: SalesInvoiceLineRow,
    tax: TaxOption | undefined,
): SalesInvoiceLineRow {
    if (!tax) {
        return { ...line, tax_id: '', tax_percent: 0, withholding_percent: 0 };
    }

    return {
        ...line,
        tax_id: tax.id,
        tax_percent: Number(tax.percentage),
        withholding_percent: taxWithholdingPercent(tax),
    };
}

function baseUnitId(item: ItemCatalogEntry | undefined): string {
    if (!item) {
        return '';
    }

    const base = item.units.find((unit) => unit.is_base === 'yes');

    return (
        base?.measurement_unit_id ?? item.units[0]?.measurement_unit_id ?? ''
    );
}

/**
 * El cliente de la factura que se edita, con la etiqueta que trae su Resource:
 * así el select lo muestra desde el primer render, mientras llega su `meta`.
 */
function clientSeed(invoice?: SalesInvoice): AjaxOption | null {
    if (!invoice?.client_id) {
        return null;
    }

    const name = invoice.client_name ?? '';

    return {
        value: invoice.client_id,
        label: invoice.client_code ? `${invoice.client_code} — ${name}` : name,
    };
}

/** El pedido de origen de la factura que se edita, si lo tiene. */
function sourceSeed(invoice?: SalesInvoice): AjaxOption | null {
    if (!invoice?.sourceable_id) {
        return null;
    }

    return {
        value: invoice.sourceable_id,
        label: invoice.sourceable_code ?? invoice.sourceable_id,
    };
}

/** En ventas el artículo se reconoce por su sku. */
function itemLabel(entry: ItemCatalogEntry): string {
    return entry.sku ? `${entry.sku} — ${entry.name}` : entry.name;
}

/**
 * Una línea por facturar del pedido, convertida en línea de la factura.
 *
 * Nace con el saldo pendiente y con las condiciones congeladas del pedido: el
 * precio, el descuento y el impuesto son los que se pactaron, no los que la
 * lista del cliente diga hoy. Por eso `list_price` sale igual que `unit_price`:
 * así una revaluación posterior lo respeta como precio pactado.
 */
function lineFromOrder(line: PendingOrderLine): SalesInvoiceLineRow {
    return {
        id: generateUUID(),
        sourceable_id: line.id,
        item_id: line.item_id,
        measurement_unit_id: line.measurement_unit_id,
        quantity: Number(line.pending_quantity),
        unit_price: Number(line.unit_price),
        list_price: Number(line.unit_price),
        discount_percent: Number(line.discount_percent),
        tax_id: line.tax_id ?? '',
        tax_percent: Number(line.tax_percent),
        withholding_percent: Number(line.withholding_percent),
        notes: line.notes ?? '',
    };
}

export function useSalesInvoiceForm({
    mode,
    options,
    initialData,
    onSuccess,
}: UseSalesInvoiceFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const configuration = useConfiguration();
    const todayRates = useTodayRates();

    /**
     * Artículos que la factura copió del pedido de origen. Entran como semilla
     * del catálogo para que sus líneas muestren el artículo sin que el usuario
     * lo vuelva a buscar.
     */
    const [orderSeeds, setOrderSeeds] = useState<ItemCatalogSeed[]>([]);

    /**
     * El catálogo de artículos ya no viaja en las props: la pantalla solo
     * conoce los que trae la factura, los que llegan con el pedido de origen y
     * los que el usuario va eligiendo.
     */
    const catalog = useItemCatalog({
        companyId,
        formatLabel: itemLabel,
        seed: [
            ...(initialData?.lines ?? [])
                .filter((line) => line.status === 'active')
                .map((line) => ({
                    id: line.item_id,
                    sku: line.item_sku,
                    name: line.item_name,
                })),
            ...orderSeeds,
        ],
    });

    /**
     * La cartera de clientes tampoco viaja en las props. Se hidrata porque la
     * pantalla usa más que la etiqueta del elegido: de su `meta` salen las
     * direcciones de entrega, los días de crédito y su lista de precio.
     */
    const client = useRemoteOption({
        url: clients.lookup(companyId).url,
        seed: clientSeed(initialData),
        hydrate: true,
    });

    /** El pedido de origen, acotado al cliente elegido. */
    const source = useRemoteOption({
        url: salesOrders.lookup(companyId).url,
        seed: sourceSeed(initialData),
        hydrate: true,
    });

    /** Lo que al pedido elegido le queda por facturar, en su propia petición. */
    const invoiceableLines = usePendingOrderLines({
        urlFor: (orderId, ids) =>
            salesOrders.invoiceableLines(
                { company: companyId, id: orderId },
                { query: { ids } },
            ).url,
    });

    const initialCurrency =
        initialData?.currency ?? configuration?.base_currency ?? '';

    /**
     * Con la corrección permitida el campo nace con la tasa del catálogo a la
     * vista, no vacío: el usuario ve con qué se va a valorar la factura antes
     * de emitirla. Prohibida la corrección, viaja vacía y la resuelve el
     * backend.
     */
    const catalogRate = (currency: string): string => {
        if (configuration?.allows_rate_override !== 'yes') {
            return '';
        }

        const rate = todayRates[currency];

        return rate === undefined ? '' : String(rate);
    };

    const today = new Date().toISOString().slice(0, 10);

    const { data, setData, post, put, transform, processing, errors, reset } =
        useForm<SalesInvoiceFormData>({
            id: initialData?.id ?? generateUUID(),
            client_id: initialData?.client_id ?? '',
            sourceable_type: initialData?.sourceable_type ?? '',
            sourceable_id: initialData?.sourceable_id ?? '',
            client_address_id: initialData?.client_address_id ?? '',
            warehouse_id:
                initialData?.warehouse_id ?? defaultWarehouseId(options),
            salesperson_id: initialData?.salesperson_id ?? '',
            invoice_series: initialData?.invoice_series ?? '',
            invoice_date: initialData?.invoice_date ?? today,
            due_date: initialData?.due_date ?? today,
            sale_type: initialData?.sale_type ?? 'credit',
            affects_inventory: initialData?.affects_inventory ?? 'yes',
            freight_amount: Number(initialData?.freight_amount ?? 0),
            /** Una factura nace en la moneda en la que la empresa lleva sus cifras. */
            currency: initialCurrency,
            exchange_rate: catalogRate(initialCurrency),
            notes: initialData?.notes ?? '',
            lines: lineRows(initialData),
        });

    /** Las condiciones comerciales del cliente elegido. */
    const clientMeta = client.optionOf(data.client_id)?.meta as
        | ClientOptionMeta
        | undefined;

    /** La lista de precio del cliente: en la factura solo sugiere precios. */
    const priceListId = clientMeta?.price_list_id ?? '';

    /** El vencimiento es la fecha de la factura más los días de crédito. */
    const dueDateFrom = (invoiceDate: string, days: number): string => {
        if (invoiceDate === '') {
            return '';
        }

        const due = new Date(`${invoiceDate}T00:00:00`);
        due.setDate(due.getDate() + days);

        return due.toISOString().slice(0, 10);
    };

    const repriceLine = (
        line: SalesInvoiceLineRow,
        listId: string,
        currency: string,
    ): SalesInvoiceLineRow => {
        if (line.item_id === '') {
            return line;
        }

        const item = catalog.itemOf(line.item_id);

        /*
         * Un artículo que la pantalla todavía no conoce —una línea cuya
         * hidratación no ha llegado— se deja intacta: revaluarla con lo que no
         * sabemos borraría el precio que ya tiene.
         */
        if (!item?.hydrated) {
            return line;
        }

        const price = resolvePrice(item, listId, currency, todayRates);

        /** Un precio pactado a mano (distinto del sugerido) se respeta. */
        if (line.unit_price !== line.list_price) {
            return { ...line, list_price: price };
        }

        return { ...line, list_price: price, unit_price: price };
    };

    /**
     * Elegir el cliente arrastra sus condiciones comerciales: vendedor
     * asignado, días de crédito y su dirección de entrega sugerida. Todo eso
     * llega en el `meta` de la opción del select remoto.
     *
     * Cambiar de cliente invalida el pedido de origen, que era de otro.
     */
    const selectClient = (option: AjaxOption | null) => {
        client.select(option);
        source.select(null);
        setOrderSeeds([]);

        const meta = (option?.meta ?? {}) as Partial<ClientOptionMeta>;
        const listId = meta.price_list_id ?? '';
        const defaultAddress = (meta.addresses ?? []).find(
            (address) =>
                address.type === 'shipping' && address.is_default === 'yes',
        );
        const days = meta.payment_term_days ?? 0;

        setData((current) => ({
            ...current,
            client_id: option?.value ?? '',
            client_address_id: defaultAddress?.id ?? '',
            sourceable_type: '',
            sourceable_id: '',
            /** Sin vendedor asignado se respeta el que la pantalla ya tenga. */
            salesperson_id: meta.salesperson_id ?? current.salesperson_id,
            /** Sin días de crédito la venta es de contado y vence el mismo día. */
            sale_type: days === 0 ? 'cash' : 'credit',
            due_date: dueDateFrom(current.invoice_date, days),
            /** La lista del cliente revalúa las líneas sin precio pactado. */
            lines: current.lines.map((line) => ({
                /** Las líneas del pedido anterior pierden su origen. */
                ...repriceLine(line, listId, current.currency),
                sourceable_id: '',
            })),
        }));
    };

    /**
     * Elegir el pedido de origen arma la factura con lo que le queda por
     * facturar: su cabecera y sus líneas pendientes, ya con precio e impuesto.
     *
     * Las líneas se piden aparte y no viajan en el `meta` del select: el saldo
     * por facturar solo interesa del pedido elegido. El cliente ya está puesto
     * —es el que acota este select—, así que de aquí solo llega lo demás.
     */
    const selectSource = async (option: AjaxOption | null) => {
        source.select(option);

        if (!option) {
            setOrderSeeds([]);

            setData((current) => ({
                ...current,
                sourceable_type: '',
                sourceable_id: '',
                /** Sin pedido de origen, sus líneas tampoco pueden venir de uno. */
                lines: current.lines.map((line) => ({
                    ...line,
                    sourceable_id: '',
                })),
            }));

            return;
        }

        const meta = option.meta as unknown as SalesOrderOptionMeta;

        setData((current) => ({
            ...current,
            sourceable_type: 'sales_order',
            sourceable_id: option.value,
            client_address_id:
                meta.client_address_id ?? current.client_address_id,
            warehouse_id: meta.warehouse_id,
            salesperson_id: meta.salesperson_id ?? current.salesperson_id,
            currency: meta.currency,
            exchange_rate: catalogRate(meta.currency),
            sale_type: meta.payment_term_days === 0 ? 'cash' : 'credit',
            due_date: dueDateFrom(current.invoice_date, meta.payment_term_days),
        }));

        const pending = await invoiceableLines.fetchLines(option.value);

        /** Un pedido sin saldo deja intacto lo que el usuario ya capturó. */
        if (pending.length === 0) {
            return;
        }

        setData('lines', pending.map(lineFromOrder));

        /** El artículo de cada línea del pedido pasa al catálogo de la pantalla. */
        setOrderSeeds(
            pending.map((line) => ({
                id: line.item_id,
                sku: line.item_sku,
                name: line.item_name,
            })),
        );
    };

    /** Cambiar la fecha de la factura arrastra su vencimiento. */
    const selectInvoiceDate = (invoiceDate: string) =>
        setData((current) => ({
            ...current,
            invoice_date: invoiceDate,
            due_date: dueDateFrom(
                invoiceDate,
                clientMeta?.payment_term_days ?? 0,
            ),
        }));

    /**
     * Cambiar la moneda de la factura reexpresa los precios sugeridos: el
     * mismo artículo cuesta otro número en otra moneda.
     */
    const selectCurrency = (currency: string) =>
        setData((current) => ({
            ...current,
            currency,
            /** Otra moneda, otra tasa: la del catálogo vuelve a la vista. */
            exchange_rate: catalogRate(currency),
            lines: current.lines.map((line) =>
                repriceLine(line, priceListId, currency),
            ),
        }));

    const addLine = () => setData('lines', [...data.lines, emptyLine()]);

    const removeLine = (index: number) =>
        setData(
            'lines',
            data.lines.length === 1
                ? [emptyLine()]
                : data.lines.filter((_, i) => i !== index),
        );

    const updateLine = <K extends keyof SalesInvoiceLineRow>(
        index: number,
        field: K,
        value: SalesInvoiceLineRow[K],
    ) =>
        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index ? { ...line, [field]: value } : line,
            ),
        );

    /** El impuesto del catálogo con ese id, si sigue activo. */
    const taxOf = (taxId: string | null | undefined): TaxOption | undefined =>
        taxId ? options.taxes.find((tax) => tax.id === taxId) : undefined;

    /**
     * Elegir el artículo trae su unidad base y el precio sugerido por la lista
     * del cliente. La opción llega del select remoto con todo eso dentro, así
     * que basta con recordarla para que el resto de la pantalla la conozca.
     */
    const selectLineItem = (index: number, option: AjaxOption | null) => {
        const item = option ? catalog.remember(option) : undefined;
        const price = resolvePrice(
            item,
            priceListId,
            data.currency,
            todayRates,
        );

        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index
                    ? withTax(
                          {
                              ...line,
                              /** Una línea recapturada ya no viene del pedido. */
                              sourceable_id: '',
                              item_id: item?.id ?? '',
                              measurement_unit_id: baseUnitId(item),
                              list_price: price,
                              unit_price: price,
                          },
                          taxOf(item?.sale_tax_id),
                      )
                    : line,
            ),
        );
    };

    /** Cambiar el impuesto de una línea trae su porcentaje y su retención. */
    const selectLineTax = (index: number, taxId: string) =>
        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index ? withTax(line, taxOf(taxId)) : line,
            ),
        );

    const lineTotals = data.lines.reduce(
        (accumulator, line) => {
            const amounts = lineAmounts(line);

            return {
                gross: round(accumulator.gross + amounts.gross, 2),
                discountAmount: round(
                    accumulator.discountAmount + amounts.discountAmount,
                    2,
                ),
                subtotal: round(accumulator.subtotal + amounts.subtotal, 2),
                taxAmount: round(accumulator.taxAmount + amounts.taxAmount, 2),
                withholdingAmount: round(
                    accumulator.withholdingAmount + amounts.withholdingAmount,
                    2,
                ),
            };
        },
        {
            gross: 0,
            discountAmount: 0,
            subtotal: 0,
            taxAmount: 0,
            withholdingAmount: 0,
        },
    );

    const totals = {
        ...lineTotals,
        freight: data.freight_amount,
        /**
         * El flete cobrado suma al total; la retención no lo baja: es un
         * impuesto que el cliente entera al fisco y se salda con su
         * comprobante, no con la factura.
         */
        total: round(
            lineTotals.subtotal + lineTotals.taxAmount + data.freight_amount,
            2,
        ),
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        transform((payload) => ({
            ...payload,
            /**
             * El campo enseña la tasa del catálogo, pero solo viaja como
             * corrección si el usuario escribió otra: así una factura con
             * fecha anterior se sigue valorando con la tasa de su día.
             */
            exchange_rate:
                payload.exchange_rate === catalogRate(payload.currency)
                    ? ''
                    : payload.exchange_rate,
            /** El precio sugerido no se persiste: la factura no lleva lista. */
            lines: payload.lines.map((line) => ({
                id: line.id,
                sourceable_type:
                    line.sourceable_id === '' ? '' : 'sales_order_line',
                sourceable_id: line.sourceable_id,
                item_id: line.item_id,
                measurement_unit_id: line.measurement_unit_id,
                quantity: line.quantity,
                unit_price: line.unit_price,
                discount_percent: line.discount_percent,
                tax_id: line.tax_id,
                tax_percent: line.tax_percent,
                withholding_percent: line.withholding_percent,
                notes: line.notes,
            })),
        }));

        if (mode === 'create') {
            post(salesInvoices.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });

            return;
        }

        if (initialData) {
            put(
                salesInvoices.update({ company: companyId, id: initialData.id })
                    .url,
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
        catalog,
        clientLookupUrl: client.url,
        clientOption: client.optionOf(data.client_id),
        /** Las condiciones del cliente elegido: direcciones, crédito, descuento. */
        client: clientMeta,
        sourceLookupUrl: source.url,
        sourceOption: source.optionOf(data.sourceable_id),
        loadingOrderLines: invoiceableLines.loading,
        orderLinesFailed: invoiceableLines.failed,
        selectClient,
        selectSource,
        selectInvoiceDate,
        selectCurrency,
        addLine,
        removeLine,
        updateLine,
        selectLineItem,
        selectLineTax,
    };
}
