import { useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import { useConfiguration } from '@/hooks/use-configuration';
import {
    useItemCatalog,
    type ItemCatalogEntry,
    type ItemCatalogSeed,
} from '@/hooks/use-item-catalog';
import { useRemoteOption } from '@/hooks/use-remote-option';
import {
    useRemoteOptionSet,
    type RemoteOptionSeed,
} from '@/hooks/use-remote-option-set';
import { useTodayRates } from '@/hooks/use-today-rates';
import { generateUUID } from '@/lib/utils';
import itemLots from '@/routes/item-lots';
import itemSerials from '@/routes/item-serials';
import purchaseInvoices from '@/routes/purchase-invoices';
import purchaseReturns from '@/routes/purchase-returns';
import suppliers from '@/routes/suppliers';
import { taxWithholdingPercent, type TaxOption } from '@/types/tax';
import type {
    PurchaseInvoiceOptionLine,
    PurchaseInvoiceOptionMeta,
    PurchaseReturn,
    PurchaseReturnOptions,
    PurchaseReturnReason,
    SupplierOptionMeta,
} from '../types/PurchaseReturn';

interface UsePurchaseReturnFormProps {
    mode: 'create' | 'edit';
    options: PurchaseReturnOptions;
    initialData?: PurchaseReturn;
    onSuccess?: () => void;
}

export interface PurchaseReturnLineRow {
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
    /** Línea de la factura que esta línea devuelve; vacía en una suelta. */
    purchase_invoice_line_id: string;
    lot_id: string;
    serial_id: string;
    /** Vacía deja que el kardex tome la ubicación por defecto de la bodega. */
    location_id: string;
    /** Motivo propio de la línea; vacío hereda el de la cabecera. */
    reason: PurchaseReturnReason | '';
    notes: string;
}

interface PurchaseReturnFormData {
    id: string;
    supplier_id: string;
    /** Factura de origen; vacía en una devolución sin factura previa. */
    purchase_invoice_id: string;
    warehouse_id: string;
    return_date: string;
    reason: PurchaseReturnReason;
    reason_detail: string;
    currency: string;
    /**
     * Corrección manual de la tasa. Vacío —el caso normal— hace que la resuelva
     * el backend con el catálogo a la fecha de la devolución.
     */
    exchange_rate: string;
    carrier: string;
    tracking_number: string;
    notes: string;
    lines: PurchaseReturnLineRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/**
 * El proveedor de la devolución que se edita, con la etiqueta que trae su
 * Resource: así el select lo muestra desde el primer render.
 */
function supplierSeed(model?: PurchaseReturn): AjaxOption | null {
    if (!model?.supplier_id) {
        return null;
    }

    const name = model.supplier_name ?? '';

    return {
        value: model.supplier_id,
        label: model.supplier_code ? `${model.supplier_code} · ${name}` : name,
    };
}

/** La factura de la que sale la mercancía, si sale de alguna. */
function invoiceSeed(model?: PurchaseReturn): AjaxOption | null {
    if (!model?.purchase_invoice_id) {
        return null;
    }

    const code = model.purchase_invoice_code ?? 'Factura de compra';

    return {
        value: model.purchase_invoice_id,
        label: model.purchase_invoice_number
            ? `${code} · ${model.purchase_invoice_number}`
            : code,
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
    line: PurchaseReturnLineRow,
    tax: TaxOption | undefined,
): PurchaseReturnLineRow {
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

/** La unidad en la que se devuelve por defecto: la base del artículo. */
function baseUnitId(item: ItemCatalogEntry | undefined): string {
    if (!item) {
        return '';
    }

    const base = item.units.find((unit) => unit.is_base === 'yes');

    return (
        base?.measurement_unit_id ?? item.units[0]?.measurement_unit_id ?? ''
    );
}

export interface PurchaseReturnTotals {
    /** Cantidad por precio, antes de cualquier rebaja. */
    gross: number;
    discountAmount: number;
    subtotal: number;
    taxAmount: number;
    /** Parte del impuesto que se entera al fisco en vez de devolverse. */
    withholdingAmount: number;
    total: number;
}

function emptyLine(): PurchaseReturnLineRow {
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
        purchase_invoice_line_id: '',
        lot_id: '',
        serial_id: '',
        location_id: '',
        reason: '',
        notes: '',
    };
}

/**
 * Solo se editan las líneas activas: las inactivas se conservan en la base por
 * la política de no borrado, pero no vuelven al formulario.
 */
function lineRows(model?: PurchaseReturn): PurchaseReturnLineRow[] {
    const rows = (model?.lines ?? [])
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
            purchase_invoice_line_id: line.purchase_invoice_line_id ?? '',
            lot_id: line.lot_id ?? '',
            serial_id: line.serial_id ?? '',
            location_id: line.location_id ?? '',
            reason: line.reason ?? ('' as const),
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine()];
}

/**
 * Espejo del cálculo del backend (`PurchaseReturnLineData`): sirve para mostrar
 * el resumen mientras se captura. El importe que se guarda siempre lo recalcula
 * el servidor.
 */
export function lineAmounts(line: PurchaseReturnLineRow): {
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

export function usePurchaseReturnForm({
    mode,
    options,
    initialData,
    onSuccess,
}: UsePurchaseReturnFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const configuration = useConfiguration();
    const todayRates = useTodayRates();

    /** El padrón de proveedores se busca contra su endpoint de opciones. */
    const supplier = useRemoteOption({
        url: suppliers.lookup(companyId).url,
        seed: supplierSeed(initialData),
    });

    /**
     * Y las facturas contra el suyo, acotadas al proveedor elegido. Se hidrata
     * porque de su `meta` salen las líneas que la devolución copia, y eso tiene
     * que estar también al abrir una devolución ya guardada.
     */
    const invoice = useRemoteOption({
        url: purchaseInvoices.lookup(companyId).url,
        seed: invoiceSeed(initialData),
        hydrate: true,
    });

    const initialCurrency =
        initialData?.currency ?? configuration?.base_currency ?? '';

    /**
     * Con la corrección permitida el campo nace con la tasa del catálogo a la
     * vista, no vacío: el usuario ve con qué se va a valorar la devolución antes
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
        useForm<PurchaseReturnFormData>({
            id: initialData?.id ?? generateUUID(),
            supplier_id: initialData?.supplier_id ?? '',
            purchase_invoice_id: initialData?.purchase_invoice_id ?? '',
            warehouse_id: initialData?.warehouse_id ?? '',
            return_date:
                initialData?.return_date ??
                new Date().toISOString().slice(0, 10),
            reason: initialData?.reason ?? 'damaged',
            reason_detail: initialData?.reason_detail ?? '',
            /** Una devolución nace en la moneda en la que la empresa lleva sus cifras. */
            currency: initialCurrency,
            exchange_rate: catalogRate(initialCurrency),
            carrier: initialData?.carrier ?? '',
            tracking_number: initialData?.tracking_number ?? '',
            notes: initialData?.notes ?? '',
            lines: lineRows(initialData),
        });

    /**
     * El catálogo de artículos ya no viaja en las props: la pantalla solo conoce
     * los que trae la devolución y los que el usuario va eligiendo.
     *
     * La semilla se recalcula con las líneas vigentes, no solo con las que trajo
     * el documento: copiar las líneas de la factura mete artículos que la
     * pantalla nunca había visto y el hook los hidrata solo.
     */
    const catalogSeed: ItemCatalogSeed[] = useMemo(() => {
        const seeds = new Map<string, ItemCatalogSeed>();

        (initialData?.lines ?? [])
            .filter((line) => line.status === 'active')
            .forEach((line) =>
                seeds.set(line.item_id, {
                    id: line.item_id,
                    code: line.item_code,
                    name: line.item_name,
                }),
            );

        data.lines.forEach((line) => {
            if (line.item_id !== '' && !seeds.has(line.item_id)) {
                seeds.set(line.item_id, { id: line.item_id });
            }
        });

        return [...seeds.values()];
    }, [initialData, data.lines]);

    const catalog = useItemCatalog({
        companyId,
        formatLabel: itemLabel,
        seed: catalogSeed,
    });

    /**
     * Lotes y series elegidos en las líneas. La semilla crece igual que la de
     * artículos: copiar las líneas de la factura arrastra sus lotes.
     */
    const lotSeed: RemoteOptionSeed[] = useMemo(
        () =>
            optionSeeds(
                (initialData?.lines ?? []).map((line) => ({
                    id: line.lot_id,
                    label: line.lot_number,
                })),
                data.lines.map((line) => line.lot_id),
            ),
        [initialData, data.lines],
    );

    const serialSeed: RemoteOptionSeed[] = useMemo(
        () =>
            optionSeeds(
                (initialData?.lines ?? []).map((line) => ({
                    id: line.serial_id,
                    label: line.serial_number,
                })),
                data.lines.map((line) => line.serial_id),
            ),
        [initialData, data.lines],
    );

    const lots = useRemoteOptionSet({
        url: itemLots.lookup(companyId).url,
        seed: lotSeed,
    });

    const serials = useRemoteOptionSet({
        url: itemSerials.lookup(companyId).url,
        seed: serialSeed,
    });

    /**
     * Cambiar la moneda trae la tasa del catálogo de esa moneda. Los precios ya
     * capturados no se tocan: son los que costó la compra.
     */
    const selectCurrency = (currency: string) =>
        setData((current) => ({
            ...current,
            currency,
            exchange_rate: catalogRate(currency),
        }));

    /**
     * Elegir el proveedor arrastra su moneda —que entra por la misma puerta que
     * el select de moneda, para que traiga su tasa—. Cambiar de proveedor
     * invalida la factura elegida, que era de otro, y con ella la trazabilidad
     * de las líneas.
     */
    const selectSupplier = (option: AjaxOption | null) => {
        supplier.select(option);
        invoice.select(null);

        const meta = (option?.meta ?? {}) as Partial<SupplierOptionMeta>;

        setData((current) => ({
            ...current,
            supplier_id: option?.value ?? '',
            purchase_invoice_id: '',
            lines: current.lines.map((line) => ({
                ...line,
                purchase_invoice_line_id: '',
            })),
        }));

        if (meta.currency) {
            selectCurrency(meta.currency);
        }
    };

    /**
     * Elegir la factura copia su moneda, pero no sus líneas: casi nunca se
     * devuelve la factura entera. Para traerlas está `copyInvoiceLines`.
     */
    const selectInvoice = (option: AjaxOption | null) => {
        invoice.select(option);

        const meta = (option?.meta ?? {}) as Partial<PurchaseInvoiceOptionMeta>;

        setData((current) => ({
            ...current,
            purchase_invoice_id: option?.value ?? '',
            /** Sin factura no hay contra qué trazar las líneas. */
            lines: current.lines.map((line) => ({
                ...line,
                purchase_invoice_line_id: option
                    ? line.purchase_invoice_line_id
                    : '',
            })),
        }));

        if (meta.currency) {
            selectCurrency(meta.currency);
        }
    };

    /** Las líneas de la factura elegida, tal como llegan en el `meta`. */
    const invoiceLines: PurchaseInvoiceOptionLine[] =
        (
            (invoice.optionOf(data.purchase_invoice_id)?.meta ??
                {}) as Partial<PurchaseInvoiceOptionMeta>
        ).lines ?? [];

    const invoiceLineOf = (
        id: string,
    ): PurchaseInvoiceOptionLine | undefined =>
        id ? invoiceLines.find((line) => line.id === id) : undefined;

    /** Cuánto queda por devolver de una línea de la factura. */
    const remainingOf = (invoiceLineId: string): number => {
        const source = invoiceLineOf(invoiceLineId);

        if (!source) {
            return 0;
        }

        return round2(
            Number(source.quantity) - Number(source.returned_quantity),
        );
    };

    /**
     * Trae las líneas de la factura al formulario, cada una ya apuntando a la
     * suya y con lo que aún queda por devolver. Sustituye lo capturado: es el
     * punto de partida de la devolución, no un añadido.
     *
     * Lo ya devuelto por completo no vuelve: no hay nada que sacar de ahí.
     */
    const copyInvoiceLines = () => {
        const pending = invoiceLines.filter(
            (line) =>
                Number(line.quantity) - Number(line.returned_quantity) > 0,
        );

        if (pending.length === 0) {
            return;
        }

        setData(
            'lines',
            pending.map((line) => ({
                id: generateUUID(),
                item_id: line.item_id,
                measurement_unit_id: line.measurement_unit_id,
                quantity: round2(
                    Number(line.quantity) - Number(line.returned_quantity),
                ),
                unit_price: Number(line.unit_price),
                discount_percent: Number(line.discount_percent),
                tax_id: line.tax_id ?? '',
                tax_percent: Number(line.tax_percent),
                withholding_percent: Number(line.withholding_percent),
                purchase_invoice_line_id: line.id,
                lot_id: line.lot_id ?? '',
                serial_id: '',
                location_id: '',
                reason: '' as const,
                notes: '',
            })),
        );
    };

    const addLine = () => setData('lines', [...data.lines, emptyLine()]);

    const removeLine = (index: number) =>
        setData(
            'lines',
            data.lines.filter((_, i) => i !== index),
        );

    const updateLine = <K extends keyof PurchaseReturnLineRow>(
        index: number,
        field: K,
        value: PurchaseReturnLineRow[K],
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
     * Cambiar de artículo invalida la unidad, el lote, la serie y la
     * trazabilidad a la línea de factura: ya no es la misma mercancía.
     */
    const setLineItem = (index: number, option: AjaxOption | null) => {
        const item = option ? catalog.remember(option) : undefined;

        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index
                    ? withTax(
                          {
                              ...line,
                              item_id: item?.id ?? '',
                              measurement_unit_id: baseUnitId(item),
                              purchase_invoice_line_id: '',
                              lot_id: '',
                              serial_id: '',
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

    const setLineLot = (index: number, option: AjaxOption | null) => {
        if (option) {
            lots.remember(option);
        }

        updateLine(index, 'lot_id', option?.value ?? '');
    };

    const setLineSerial = (index: number, option: AjaxOption | null) => {
        if (option) {
            serials.remember(option);
        }

        updateLine(index, 'serial_id', option?.value ?? '');
    };

    /**
     * Atar una línea a la de la factura copia lo que se compró: artículo,
     * unidad, precio, sus cargos y el lote recibido. Es lo que hace que el
     * backend pueda comprobar que no se devuelve más de lo comprado.
     */
    const setLineInvoiceLine = (index: number, invoiceLineId: string) => {
        const source = invoiceLineOf(invoiceLineId);

        setData(
            'lines',
            data.lines.map((line, i) => {
                if (i !== index) {
                    return line;
                }

                if (!source) {
                    return { ...line, purchase_invoice_line_id: '' };
                }

                return {
                    ...line,
                    purchase_invoice_line_id: source.id,
                    item_id: source.item_id,
                    measurement_unit_id: source.measurement_unit_id,
                    unit_price: Number(source.unit_price),
                    discount_percent: Number(source.discount_percent),
                    tax_id: source.tax_id ?? '',
                    tax_percent: Number(source.tax_percent),
                    withholding_percent: Number(source.withholding_percent),
                    /** El lote que vuelve es el que llegó en esa línea. */
                    lot_id: source.lot_id ?? '',
                };
            }),
        );
    };

    /** Las ubicaciones de la bodega elegida: la mercancía sale de una suya. */
    const locations = options.locations.filter(
        (location) => location.warehouse_id === data.warehouse_id,
    );

    /**
     * Cambiar de bodega invalida las ubicaciones ya elegidas: eran de la
     * anterior.
     */
    const selectWarehouse = (warehouseId: string) =>
        setData((current) => ({
            ...current,
            warehouse_id: warehouseId,
            lines: current.lines.map((line) => ({ ...line, location_id: '' })),
        }));

    const totals: PurchaseReturnTotals = data.lines.reduce(
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
                total: round2(accumulator.total + amounts.total),
            };
        },
        {
            gross: 0,
            discountAmount: 0,
            subtotal: 0,
            taxAmount: 0,
            withholdingAmount: 0,
            total: 0,
        },
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        /**
         * El campo enseña la tasa del catálogo, pero solo viaja como corrección
         * si el usuario escribió otra: así una devolución con fecha anterior se
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
            post(purchaseReturns.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                purchaseReturns.update({
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
        invoiceLookupUrl: invoice.url,
        invoiceOption: invoice.optionOf(data.purchase_invoice_id),
        selectInvoice,
        invoiceLines,
        remainingOf,
        copyInvoiceLines,
        selectCurrency,
        selectWarehouse,
        locations,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineTax,
        setLineLot,
        setLineSerial,
        setLineInvoiceLine,
        catalog,
        lots,
        serials,
    };
}

/**
 * Une lo que trajo el documento con lo que las líneas tienen ahora, sin
 * repetidos y sin vacíos. Las etiquetas de lo nuevo las resuelve la hidratación.
 */
function optionSeeds(
    saved: Array<{ id: string | null; label?: string | null }>,
    current: string[],
): RemoteOptionSeed[] {
    const seeds = new Map<string, RemoteOptionSeed>();

    saved.forEach((entry) => {
        if (entry.id) {
            seeds.set(entry.id, { id: entry.id, label: entry.label });
        }
    });

    current.forEach((id) => {
        if (id !== '' && !seeds.has(id)) {
            seeds.set(id, { id });
        }
    });

    return [...seeds.values()];
}
