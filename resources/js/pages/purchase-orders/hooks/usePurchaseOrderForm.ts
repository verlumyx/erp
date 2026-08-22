import { useForm, usePage } from '@inertiajs/react';
import type { AjaxOption } from '@/components/select2-ajax';
import { useConfiguration } from '@/hooks/use-configuration';
import {
    useItemCatalog,
    type ItemCatalogEntry,
} from '@/hooks/use-item-catalog';
import { useRemoteOption } from '@/hooks/use-remote-option';
import { useTodayRates } from '@/hooks/use-today-rates';
import { convertAmount } from '@/lib/money';
import { generateUUID } from '@/lib/utils';
import purchaseOrders from '@/routes/purchase-orders';
import suppliers from '@/routes/suppliers';
import { taxWithholdingPercent, type TaxOption } from '@/types/tax';
import type {
    PurchaseOrder,
    PurchaseOrderOptions,
    SupplierOptionMeta,
} from '../types/PurchaseOrder';

interface UsePurchaseOrderFormProps {
    mode: 'create' | 'edit';
    options: PurchaseOrderOptions;
    initialData?: PurchaseOrder;
    onSuccess?: () => void;
}

export interface PurchaseOrderLineRow {
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
    notes: string;
}

interface PurchaseOrderFormData {
    id: string;
    supplier_id: string;
    warehouse_id: string;
    order_date: string;
    expected_date: string;
    supplier_reference: string;
    currency: string;
    /**
     * Corrección manual de la tasa. Vacío —el caso normal— hace que la resuelva
     * el backend con el catálogo a la fecha de la orden.
     */
    exchange_rate: string;
    payment_term_days: number;
    discount_amount: number;
    notes: string;
    lines: PurchaseOrderLineRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/**
 * El proveedor de la orden que se edita, con la etiqueta que trae su Resource:
 * así el select lo muestra desde el primer render, sin ir al servidor.
 */
function supplierSeed(order?: PurchaseOrder): AjaxOption | null {
    if (!order?.supplier_id) {
        return null;
    }

    const name = order.supplier_name ?? '';

    return {
        value: order.supplier_id,
        label: order.supplier_code ? `${order.supplier_code} · ${name}` : name,
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
    line: PurchaseOrderLineRow,
    tax: TaxOption | undefined,
): PurchaseOrderLineRow {
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

/** La unidad en la que se pide por defecto: la base del artículo. */
function baseUnitId(item: ItemCatalogEntry | undefined): string {
    if (!item) {
        return '';
    }

    const base = item.units.find((unit) => unit.is_base === 'yes');

    return (
        base?.measurement_unit_id ?? item.units[0]?.measurement_unit_id ?? ''
    );
}

export interface PurchaseOrderTotals {
    /** Cantidad por precio, antes de cualquier rebaja. */
    gross: number;
    /** Suma de las rebajas de línea; el descuento global va aparte. */
    discountAmount: number;
    subtotal: number;
    taxAmount: number;
    total: number;
}

function emptyLine(): PurchaseOrderLineRow {
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
        notes: '',
    };
}

/**
 * Solo se editan las líneas activas: las inactivas se conservan en la base por
 * la política de no borrado, pero no vuelven al formulario.
 */
function lineRows(order?: PurchaseOrder): PurchaseOrderLineRow[] {
    const rows = (order?.lines ?? [])
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
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine()];
}

/**
 * Espejo del cálculo del backend (`PurchaseOrderLineData`): sirve para mostrar
 * el resumen mientras se captura. El importe que se guarda siempre lo recalcula
 * el servidor.
 */
export function lineAmounts(line: PurchaseOrderLineRow): {
    gross: number;
    discountAmount: number;
    subtotal: number;
    taxAmount: number;
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
        total: round2(subtotal + taxAmount),
    };
}

function round2(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

export function usePurchaseOrderForm({
    mode,
    options,
    initialData,
    onSuccess,
}: UsePurchaseOrderFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const configuration = useConfiguration();
    const todayRates = useTodayRates();

    /**
     * El catálogo de artículos ya no viaja en las props: la pantalla solo
     * conoce los que trae la orden y los que el usuario va eligiendo.
     */
    const catalog = useItemCatalog({
        companyId,
        formatLabel: itemLabel,
        seed: (initialData?.lines ?? [])
            .filter((line) => line.status === 'active')
            .map((line) => ({
                id: line.item_id,
                code: line.item_code,
                name: line.item_name,
            })),
    });

    /**
     * El padrón de proveedores tampoco viaja en las props: la cabecera lo busca
     * contra su endpoint de opciones y solo recuerda el elegido.
     */
    const supplier = useRemoteOption({
        url: suppliers.lookup(companyId).url,
        seed: supplierSeed(initialData),
    });

    const initialCurrency =
        initialData?.currency ?? configuration?.base_currency ?? '';

    /**
     * Con la corrección permitida el campo nace con la tasa del catálogo a la
     * vista, no vacío: el usuario ve con qué se va a valorar la orden antes de
     * guardarla. Prohibida la corrección, viaja vacía y la resuelve el backend.
     */
    const catalogRate = (currency: string): string => {
        if (configuration?.allows_rate_override !== 'yes') {
            return '';
        }

        const rate = todayRates[currency];

        return rate === undefined ? '' : String(rate);
    };

    const { data, setData, post, put, transform, processing, errors, reset } =
        useForm<PurchaseOrderFormData>({
            id: initialData?.id ?? generateUUID(),
            supplier_id: initialData?.supplier_id ?? '',
            warehouse_id: initialData?.warehouse_id ?? '',
            order_date:
                initialData?.order_date ??
                new Date().toISOString().slice(0, 10),
            expected_date: initialData?.expected_date ?? '',
            supplier_reference: initialData?.supplier_reference ?? '',
            /** Una orden nace en la moneda en la que la empresa lleva sus cifras. */
            currency: initialCurrency,
            exchange_rate: catalogRate(initialCurrency),
            payment_term_days: Number(initialData?.payment_term_days ?? 0),
            discount_amount: Number(initialData?.discount_amount ?? 0),
            notes: initialData?.notes ?? '',
            lines: lineRows(initialData),
        });

    /**
     * Cambiar la moneda de la orden trae la tasa del catálogo de esa moneda.
     * Los costos ya capturados no se tocan: los pactó el usuario.
     */
    const selectCurrency = (currency: string) =>
        setData((current) => ({
            ...current,
            currency,
            exchange_rate: catalogRate(currency),
        }));

    /**
     * Elegir el proveedor arrastra sus condiciones: sus días de crédito y su
     * moneda, que entra por la misma puerta que el select de moneda para que
     * traiga su tasa. Ambos quedan editables.
     */
    const selectSupplier = (option: AjaxOption | null) => {
        supplier.select(option);

        const meta = (option?.meta ?? {}) as Partial<SupplierOptionMeta>;

        setData((current) => ({
            ...current,
            supplier_id: option?.value ?? '',
            payment_term_days:
                meta.payment_term_days ?? current.payment_term_days,
        }));

        if (meta.currency) {
            selectCurrency(meta.currency);
        }
    };

    const addLine = () => setData('lines', [...data.lines, emptyLine()]);

    const removeLine = (index: number) =>
        setData(
            'lines',
            data.lines.filter((_, i) => i !== index),
        );

    const updateLine = <K extends keyof PurchaseOrderLineRow>(
        index: number,
        field: K,
        value: PurchaseOrderLineRow[K],
    ) =>
        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index ? { ...line, [field]: value } : line,
            ),
        );

    /**
     * El costo estándar del artículo se lleva en la moneda de la empresa: si la
     * orden se emite en otra, se reexpresa antes de ofrecerlo. Es una
     * sugerencia y el usuario puede pactar otro precio con el proveedor.
     */
    const costInOrderCurrency = (cost: number): number => {
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
        const cost = costInOrderCurrency(Number(item?.standard_cost ?? 0));

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

    const totals: PurchaseOrderTotals = data.lines.reduce(
        (accumulator, line) => {
            const amounts = lineAmounts(line);

            return {
                gross: round2(accumulator.gross + amounts.gross),
                discountAmount: round2(
                    accumulator.discountAmount + amounts.discountAmount,
                ),
                subtotal: round2(accumulator.subtotal + amounts.subtotal),
                taxAmount: round2(accumulator.taxAmount + amounts.taxAmount),
                total: 0,
            };
        },
        { gross: 0, discountAmount: 0, subtotal: 0, taxAmount: 0, total: 0 },
    );

    totals.total = round2(
        totals.subtotal - data.discount_amount + totals.taxAmount,
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        /**
         * El campo enseña la tasa del catálogo, pero solo viaja como corrección
         * si el usuario escribió otra: así una orden con fecha anterior se sigue
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
            post(purchaseOrders.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                purchaseOrders.update({
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
        selectCurrency,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineTax,
        catalog,
    };
}
