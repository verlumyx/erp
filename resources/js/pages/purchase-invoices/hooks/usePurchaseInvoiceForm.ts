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
import purchaseInvoices from '@/routes/purchase-invoices';
import purchaseOrders from '@/routes/purchase-orders';
import suppliers from '@/routes/suppliers';
import { taxWithholdingPercent, type TaxOption } from '@/types/tax';
import type {
    PurchaseInvoice,
    PurchaseInvoiceOptions,
    PurchaseOrderOptionMeta,
    SupplierOptionMeta,
} from '../types/PurchaseInvoice';

interface UsePurchaseInvoiceFormProps {
    mode: 'create' | 'edit';
    options: PurchaseInvoiceOptions;
    initialData?: PurchaseInvoice;
    onSuccess?: () => void;
}

export interface PurchaseInvoiceLineRow {
    id: string;
    item_id: string;
    measurement_unit_id: string;
    quantity: number;
    unit_price: number;
    discount_percent: number;
    /** Impuesto del catálogo. De él salen los dos porcentajes de abajo. */
    tax_id: string;
    tax_percent: number;
    withholding_percent: number;
    /** Línea de la orden que esta línea factura; vacías en una factura directa. */
    sourceable_type: string;
    sourceable_id: string;
    notes: string;
}

interface PurchaseInvoiceFormData {
    id: string;
    supplier_id: string;
    warehouse_id: string;
    supplier_invoice_number: string;
    supplier_invoice_series: string;
    invoice_date: string;
    received_date: string;
    /** Vacío lo deriva el backend con los días de crédito del proveedor. */
    due_date: string;
    /** Documento origen: los dos viajan juntos o ninguno. */
    sourceable_type: string;
    sourceable_id: string;
    currency: string;
    /**
     * Corrección manual de la tasa. Vacío —el caso normal— hace que la resuelva
     * el backend con el catálogo a la fecha de la factura.
     */
    exchange_rate: string;
    affects_inventory: 'yes' | 'no';
    discount_amount: number;
    freight_amount: number;
    other_charges: number;
    notes: string;
    lines: PurchaseInvoiceLineRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/** Alias del morph map con el que viaja la orden de compra como origen. */
const PURCHASE_ORDER = 'purchase_order';

/** Y el de su línea, con el que cada línea facturada apunta a la que pidió. */
const PURCHASE_ORDER_LINE = 'purchase_order_line';

/**
 * El proveedor de la factura que se edita, con la etiqueta que trae su
 * Resource: así el select lo muestra desde el primer render.
 */
function supplierSeed(invoice?: PurchaseInvoice): AjaxOption | null {
    if (!invoice?.supplier_id) {
        return null;
    }

    const name = invoice.supplier_name ?? '';

    return {
        value: invoice.supplier_id,
        label: invoice.supplier_code
            ? `${invoice.supplier_code} · ${name}`
            : name,
    };
}

/** La orden origen de la factura que se edita, si nació de una. */
function sourceSeed(invoice?: PurchaseInvoice): AjaxOption | null {
    if (!invoice?.sourceable_id) {
        return null;
    }

    return {
        value: invoice.sourceable_id,
        label: invoice.sourceable_code ?? 'Orden de compra',
    };
}

/** En compras el artículo se reconoce por su código. */
function itemLabel(entry: ItemCatalogEntry): string {
    return entry.code ? `${entry.code} · ${entry.name}` : entry.name;
}

/**
 * La línea con un impuesto del catálogo aplicado: sus dos porcentajes salen de
 * ahí y ya no se capturan a mano. Sin impuesto, ambos vuelven a cero.
 */
function withTax(
    line: PurchaseInvoiceLineRow,
    tax: TaxOption | undefined,
): PurchaseInvoiceLineRow {
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

/** La unidad en la que se compra por defecto: la base del artículo. */
function baseUnitId(item: ItemCatalogEntry | undefined): string {
    if (!item) {
        return '';
    }

    const base = item.units.find((unit) => unit.is_base === 'yes');

    return (
        base?.measurement_unit_id ?? item.units[0]?.measurement_unit_id ?? ''
    );
}

export interface PurchaseInvoiceTotals {
    /** Cantidad por costo, antes de cualquier rebaja. */
    gross: number;
    /** Suma de las rebajas de línea; el descuento global va aparte. */
    discountAmount: number;
    subtotal: number;
    taxAmount: number;
    /** Parte del impuesto que se entera al fisco en vez de pagarse al proveedor. */
    withholdingAmount: number;
    total: number;
    /** Lo que finalmente se le paga al proveedor. */
    payable: number;
}

function emptyLine(): PurchaseInvoiceLineRow {
    return {
        id: generateUUID(),
        item_id: '',
        measurement_unit_id: '',
        quantity: 1,
        unit_price: 0,
        discount_percent: 0,
        tax_id: '',
        tax_percent: 0,
        withholding_percent: 0,
        sourceable_type: '',
        sourceable_id: '',
        notes: '',
    };
}

/**
 * Una línea por facturar de la orden, convertida en línea de la factura.
 *
 * Nace con el saldo pendiente y con las condiciones congeladas de la orden: el
 * costo, el descuento y el impuesto son los que se pactaron, no los que el
 * catálogo tenga hoy.
 */
function lineFromOrder(line: PendingOrderLine): PurchaseInvoiceLineRow {
    return {
        id: generateUUID(),
        item_id: line.item_id,
        measurement_unit_id: line.measurement_unit_id,
        quantity: Number(line.pending_quantity),
        unit_price: Number(line.unit_price),
        discount_percent: Number(line.discount_percent),
        tax_id: line.tax_id ?? '',
        tax_percent: Number(line.tax_percent),
        withholding_percent: Number(line.withholding_percent),
        sourceable_type: PURCHASE_ORDER_LINE,
        sourceable_id: line.id,
        notes: line.notes ?? '',
    };
}

/**
 * Solo se editan las líneas activas: las inactivas se conservan en la base por
 * la política de no borrado, pero no vuelven al formulario.
 */
function lineRows(invoice?: PurchaseInvoice): PurchaseInvoiceLineRow[] {
    const rows = (invoice?.lines ?? [])
        .filter((line) => line.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((line) => ({
            id: line.id,
            item_id: line.item_id,
            measurement_unit_id: line.measurement_unit_id,
            quantity: Number(line.quantity),
            unit_price: Number(line.unit_price),
            discount_percent: Number(line.discount_percent),
            tax_id: line.tax_id ?? '',
            tax_percent: Number(line.tax_percent),
            withholding_percent: Number(line.withholding_percent),
            sourceable_type: line.sourceable_type ?? '',
            sourceable_id: line.sourceable_id ?? '',
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine()];
}

/**
 * Espejo del cálculo del backend (`PurchaseInvoiceLineData`): sirve para
 * mostrar el resumen mientras se captura. El importe que se guarda siempre lo
 * recalcula el servidor.
 */
export function lineAmounts(line: PurchaseInvoiceLineRow): {
    gross: number;
    discountAmount: number;
    subtotal: number;
    taxAmount: number;
    withholdingAmount: number;
    total: number;
} {
    const gross = line.quantity * line.unit_price;
    const discountAmount = round2((gross * line.discount_percent) / 100);
    const subtotal = round2(gross - discountAmount);
    const taxAmount = round2((subtotal * line.tax_percent) / 100);

    return {
        gross: round2(gross),
        discountAmount,
        subtotal,
        taxAmount,
        /** La retención se practica sobre el impuesto, no sobre la base. */
        withholdingAmount: round2((taxAmount * line.withholding_percent) / 100),
        total: round2(subtotal + taxAmount),
    };
}

function round2(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

export function usePurchaseInvoiceForm({
    mode,
    options,
    initialData,
    onSuccess,
}: UsePurchaseInvoiceFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const configuration = useConfiguration();
    const todayRates = useTodayRates();

    /**
     * Artículos que la factura copió de la orden de origen. Entran como semilla
     * del catálogo para que sus líneas muestren el artículo sin que el usuario
     * lo vuelva a buscar.
     */
    const [orderSeeds, setOrderSeeds] = useState<ItemCatalogSeed[]>([]);

    /**
     * El catálogo de artículos ya no viaja en las props: la pantalla solo
     * conoce los que trae la factura, los que llegan con la orden de origen y
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
                    code: line.item_code,
                    name: line.item_name,
                })),
            ...orderSeeds,
        ],
    });

    /** El padrón de proveedores se busca contra su endpoint de opciones. */
    const supplier = useRemoteOption({
        url: suppliers.lookup(companyId).url,
        seed: supplierSeed(initialData),
    });

    /** Y las órdenes de compra contra el suyo, acotadas al proveedor elegido. */
    const sourceOrder = useRemoteOption({
        url: purchaseOrders.lookup(companyId).url,
        seed: sourceSeed(initialData),
    });

    /** Lo que a la orden elegida le queda por facturar, en su propia petición. */
    const invoiceableLines = usePendingOrderLines({
        urlFor: (orderId, ids) =>
            purchaseOrders.invoiceableLines(
                { company: companyId, id: orderId },
                { query: { ids } },
            ).url,
    });

    const initialCurrency =
        initialData?.currency ?? configuration?.base_currency ?? '';

    /**
     * Con la corrección permitida el campo nace con la tasa del catálogo a la
     * vista, no vacío: el usuario ve con qué se va a valorar la factura antes
     * de guardarla. Prohibida la corrección, viaja vacía y la resuelve el
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
        useForm<PurchaseInvoiceFormData>({
            id: initialData?.id ?? generateUUID(),
            supplier_id: initialData?.supplier_id ?? '',
            warehouse_id: initialData?.warehouse_id ?? '',
            supplier_invoice_number: initialData?.supplier_invoice_number ?? '',
            supplier_invoice_series: initialData?.supplier_invoice_series ?? '',
            invoice_date:
                initialData?.invoice_date ??
                new Date().toISOString().slice(0, 10),
            received_date: initialData?.received_date ?? '',
            due_date: initialData?.due_date ?? '',
            sourceable_type: initialData?.sourceable_type ?? '',
            sourceable_id: initialData?.sourceable_id ?? '',
            /** Una factura nace en la moneda en la que la empresa lleva sus cifras. */
            currency: initialCurrency,
            exchange_rate: catalogRate(initialCurrency),
            affects_inventory: initialData?.affects_inventory ?? 'yes',
            discount_amount: Number(initialData?.discount_amount ?? 0),
            freight_amount: Number(initialData?.freight_amount ?? 0),
            other_charges: Number(initialData?.other_charges ?? 0),
            notes: initialData?.notes ?? '',
            lines: lineRows(initialData),
        });

    /**
     * Cambiar la moneda de la factura trae la tasa del catálogo de esa moneda.
     * Los costos ya capturados no se tocan: son los que imprimió el proveedor.
     */
    const selectCurrency = (currency: string) =>
        setData((current) => ({
            ...current,
            currency,
            exchange_rate: catalogRate(currency),
        }));

    /**
     * El vencimiento sugerido: la fecha de emisión más los días de crédito.
     *
     * La cuenta va en UTC a propósito. Sumar días sobre una fecha local y luego
     * serializarla con `toISOString()` corre el resultado un día en los husos
     * al este de Greenwich; aquí no hay hora, solo un día del calendario.
     */
    const dueDateFrom = (invoiceDate: string, days: number): string => {
        if (!invoiceDate) {
            return '';
        }

        const date = new Date(`${invoiceDate}T00:00:00Z`);

        if (Number.isNaN(date.getTime())) {
            return '';
        }

        date.setUTCDate(date.getUTCDate() + days);

        return date.toISOString().slice(0, 10);
    };

    /**
     * Elegir el proveedor arrastra sus condiciones: su moneda —que entra por la
     * misma puerta que el select de moneda, para que traiga su tasa— y su
     * crédito, con el que se propone el vencimiento. Cambiar de proveedor
     * invalida la orden origen, que era de otro.
     */
    const selectSupplier = (option: AjaxOption | null) => {
        supplier.select(option);
        sourceOrder.select(null);
        setOrderSeeds([]);

        const meta = (option?.meta ?? {}) as Partial<SupplierOptionMeta>;

        setData((current) => ({
            ...current,
            supplier_id: option?.value ?? '',
            sourceable_type: '',
            sourceable_id: '',
            /** Las líneas que venían de la orden anterior pierden su origen. */
            lines: current.lines.map((line) => ({
                ...line,
                sourceable_type: '',
                sourceable_id: '',
            })),
            due_date:
                meta.payment_term_days === undefined
                    ? current.due_date
                    : dueDateFrom(current.invoice_date, meta.payment_term_days),
        }));

        if (meta.currency) {
            selectCurrency(meta.currency);
        }
    };

    /**
     * Elegir la orden origen arma la factura con lo que le queda por facturar:
     * su cabecera —bodega y moneda— y sus líneas pendientes, ya con el costo,
     * el descuento y el impuesto que se pactaron con el proveedor.
     *
     * Las líneas se piden aparte y no viajan en el `meta` del select: el saldo
     * por facturar solo interesa de la orden elegida. La cantidad queda
     * editable hacia abajo —manda lo que el proveedor imprimió— pero el backend
     * no admite más de lo pendiente.
     */
    const selectSourceOrder = async (option: AjaxOption | null) => {
        sourceOrder.select(option);

        if (!option) {
            setOrderSeeds([]);

            setData((current) => ({
                ...current,
                sourceable_type: '',
                sourceable_id: '',
                /** Sin orden origen, sus líneas tampoco pueden venir de una. */
                lines: current.lines.map((line) => ({
                    ...line,
                    sourceable_type: '',
                    sourceable_id: '',
                })),
            }));

            return;
        }

        const meta = (option.meta ?? {}) as Partial<PurchaseOrderOptionMeta>;

        setData((current) => ({
            ...current,
            sourceable_type: PURCHASE_ORDER,
            sourceable_id: option.value,
            warehouse_id: meta.warehouse_id ?? current.warehouse_id,
        }));

        if (meta.currency) {
            selectCurrency(meta.currency);
        }

        const pending = await invoiceableLines.fetchLines(option.value);

        /** Una orden sin saldo deja intacto lo que el usuario ya capturó. */
        if (pending.length === 0) {
            return;
        }

        setData('lines', pending.map(lineFromOrder));

        /** El artículo de cada línea de la orden pasa al catálogo de la pantalla. */
        setOrderSeeds(
            pending.map((line) => ({
                id: line.item_id,
                code: line.item_code,
                name: line.item_name,
            })),
        );
    };

    const addLine = () => setData('lines', [...data.lines, emptyLine()]);

    const removeLine = (index: number) =>
        setData(
            'lines',
            data.lines.filter((_, i) => i !== index),
        );

    const updateLine = <K extends keyof PurchaseInvoiceLineRow>(
        index: number,
        field: K,
        value: PurchaseInvoiceLineRow[K],
    ) =>
        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index ? { ...line, [field]: value } : line,
            ),
        );

    /**
     * El costo estándar del artículo se lleva en la moneda de la empresa: si la
     * factura viene en otra, se reexpresa antes de ofrecerlo. Es una
     * sugerencia: manda el costo que imprimió el proveedor.
     */
    const costInInvoiceCurrency = (cost: number): number => {
        const baseCurrency = configuration?.base_currency;

        if (!baseCurrency || baseCurrency === data.currency) {
            return cost;
        }

        return (
            convertAmount(
                cost,
                todayRates[baseCurrency],
                todayRates[data.currency],
            ) ?? 0
        );
    };

    /** El impuesto del catálogo con ese id, si sigue activo. */
    const taxOf = (taxId: string | null | undefined): TaxOption | undefined =>
        taxId ? options.taxes.find((tax) => tax.id === taxId) : undefined;

    /**
     * Cambiar de artículo invalida la unidad elegida: se resuelve en una sola
     * pasada con lo que trae la opción del select remoto (unidades, costo e
     * impuesto de compra).
     */
    const setLineItem = (index: number, option: AjaxOption | null) => {
        const item = option ? catalog.remember(option) : undefined;
        const cost = costInInvoiceCurrency(Number(item?.standard_cost ?? 0));

        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index
                    ? withTax(
                          {
                              ...line,
                              item_id: item?.id ?? '',
                              measurement_unit_id: baseUnitId(item),
                              unit_price:
                                  line.unit_price > 0 ? line.unit_price : cost,
                              /** Otro artículo ya no es la línea que pidió la orden. */
                              sourceable_type: '',
                              sourceable_id: '',
                          },
                          taxOf(item?.purchase_tax_id),
                      )
                    : line,
            ),
        );
    };

    /** Cambiar el impuesto de una línea trae su porcentaje y su retención. */
    const setLineTax = (index: number, taxId: string) =>
        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index ? withTax(line, taxOf(taxId)) : line,
            ),
        );

    const totals: PurchaseInvoiceTotals = data.lines.reduce(
        (accumulator, line) => {
            const amounts = lineAmounts(line);

            return {
                gross: round2(accumulator.gross + amounts.gross),
                discountAmount: round2(
                    accumulator.discountAmount + amounts.discountAmount,
                ),
                subtotal: round2(accumulator.subtotal + amounts.subtotal),
                taxAmount: round2(accumulator.taxAmount + amounts.taxAmount),
                withholdingAmount: round2(
                    accumulator.withholdingAmount + amounts.withholdingAmount,
                ),
                total: 0,
                payable: 0,
            };
        },
        {
            gross: 0,
            discountAmount: 0,
            subtotal: 0,
            taxAmount: 0,
            withholdingAmount: 0,
            total: 0,
            payable: 0,
        },
    );

    totals.total = round2(
        totals.subtotal -
            data.discount_amount +
            totals.taxAmount +
            data.freight_amount +
            data.other_charges,
    );

    /** La retención no baja el valor de la factura, baja lo que se paga. */
    totals.payable = round2(totals.total - totals.withholdingAmount);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        /**
         * El campo enseña la tasa del catálogo, pero solo viaja como corrección
         * si el usuario escribió otra: así una factura con fecha anterior se
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
            post(purchaseInvoices.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                purchaseInvoices.update({
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
        supplierLookupUrl: supplier.url,
        supplierOption: supplier.optionOf(data.supplier_id),
        selectSupplier,
        sourceOrderLookupUrl: sourceOrder.url,
        sourceOrderOption: sourceOrder.optionOf(data.sourceable_id),
        selectSourceOrder,
        loadingOrderLines: invoiceableLines.loading,
        orderLinesFailed: invoiceableLines.failed,
        selectCurrency,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineTax,
        catalog,
    };
}
