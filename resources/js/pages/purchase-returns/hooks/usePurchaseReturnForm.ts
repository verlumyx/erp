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
import { useTodayRates } from '@/hooks/use-today-rates';
import { generateUUID } from '@/lib/utils';
import purchaseInvoices from '@/routes/purchase-invoices';
import purchaseReturns from '@/routes/purchase-returns';
import suppliers from '@/routes/suppliers';
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

/**
 * Lo que la pantalla captura de una línea: qué vuelve, cuánto y de qué bodega.
 *
 * El costo, el impuesto y el descuento no están aquí a propósito: los copia el
 * backend de la línea facturada. El lote, la serie y la ubicación tampoco: los
 * pide el despacho que la devolución genera al confirmarse.
 */
export interface PurchaseReturnLineRow {
    id: string;
    item_id: string;
    measurement_unit_id: string;
    /** Bodega desde la que sale; nace con la de la cabecera. */
    warehouse_id: string;
    quantity: number;
    /** Línea de la factura que esta línea devuelve; vacía en una suelta. */
    purchase_invoice_line_id: string;
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

/**
 * Lo que el resumen enseña mientras se captura. El dinero no está aquí: lo
 * decide el backend con el precio de la factura, así que hasta guardar no hay
 * importe que mostrar.
 */
export interface PurchaseReturnTotals {
    quantity: number;
}

function emptyLine(warehouseId: string): PurchaseReturnLineRow {
    return {
        id: generateUUID(),
        item_id: '',
        measurement_unit_id: '',
        warehouse_id: warehouseId,
        quantity: 1,
        purchase_invoice_line_id: '',
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
            warehouse_id: line.warehouse_id ?? '',
            quantity: Number(line.quantity),
            purchase_invoice_line_id: line.purchase_invoice_line_id ?? '',
            reason: line.reason ?? ('' as const),
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine(model?.warehouse_id ?? '')];
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
     * Cambiar la moneda trae la tasa del catálogo de esa moneda. No toca las
     * líneas: lo que se devuelve costó lo que costó.
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
                /** La bodega de la factura si la trae; si no, la de la devolución. */
                warehouse_id: line.warehouse_id ?? data.warehouse_id,
                quantity: round2(
                    Number(line.quantity) - Number(line.returned_quantity),
                ),
                purchase_invoice_line_id: line.id,
                reason: '' as const,
                notes: '',
            })),
        );
    };

    const addLine = () =>
        setData('lines', [...data.lines, emptyLine(data.warehouse_id)]);

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

    /**
     * Cambiar de artículo invalida la unidad y la trazabilidad a la línea de
     * factura: ya no es la misma mercancía.
     */
    const setLineItem = (index: number, option: AjaxOption | null) => {
        const item = option ? catalog.remember(option) : undefined;

        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index
                    ? {
                          ...line,
                          item_id: item?.id ?? '',
                          measurement_unit_id: baseUnitId(item),
                          purchase_invoice_line_id: '',
                      }
                    : line,
            ),
        );
    };

    /**
     * Atar una línea a la de la factura copia qué se compró y en qué unidad. Es
     * lo que hace que el backend pueda comprobar que no se devuelve más de lo
     * comprado, y de ahí sale el precio con el que se acredita.
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
                    warehouse_id: source.warehouse_id ?? line.warehouse_id,
                };
            }),
        );
    };

    /**
     * Cambiar la bodega de la cabecera arrastra las líneas que todavía seguían
     * a la anterior: las que alguien apartó a mano se quedan donde están.
     */
    const selectWarehouse = (warehouseId: string) =>
        setData((current) => ({
            ...current,
            warehouse_id: warehouseId,
            lines: current.lines.map((line) =>
                line.warehouse_id === current.warehouse_id ||
                line.warehouse_id === ''
                    ? { ...line, warehouse_id: warehouseId }
                    : line,
            ),
        }));

    const totals: PurchaseReturnTotals = {
        quantity: round2(
            data.lines.reduce((sum, line) => sum + Number(line.quantity), 0),
        ),
    };

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
        warehouses: options.warehouses,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineInvoiceLine,
        catalog,
    };
}
