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
import purchaseCreditNotes from '@/routes/purchase-credit-notes';
import purchaseInvoices from '@/routes/purchase-invoices';
import purchaseReturns from '@/routes/purchase-returns';
import suppliers from '@/routes/suppliers';
import { taxWithholdingPercent, type TaxOption } from '@/types/tax';
import type {
    PurchaseCreditNote,
    PurchaseCreditNoteOptions,
    PurchaseCreditNoteReason,
    PurchaseInvoiceOptionLine,
    PurchaseInvoiceOptionMeta,
    PurchaseReturnOptionMeta,
    SupplierOptionMeta,
} from '../types/PurchaseCreditNote';

interface UsePurchaseCreditNoteFormProps {
    mode: 'create' | 'edit';
    options: PurchaseCreditNoteOptions;
    initialData?: PurchaseCreditNote;
    onSuccess?: () => void;
}

export interface PurchaseCreditNoteLineRow {
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
    /** Línea de la factura que esta línea acredita; vacía en una nota suelta. */
    purchase_invoice_line_id: string;
    /** Solo hace falta cuando la nota saca mercancía del inventario. */
    warehouse_id: string;
    notes: string;
}

interface PurchaseCreditNoteFormData {
    id: string;
    supplier_id: string;
    /** Factura afectada; vacía en una nota que no corrige una en concreto. */
    purchase_invoice_id: string;
    /** Devolución que la origina; vacía en una nota que no nace de una. */
    purchase_return_id: string;
    supplier_document_number: string;
    note_date: string;
    reason: PurchaseCreditNoteReason;
    reason_detail: string;
    currency: string;
    /**
     * Corrección manual de la tasa. Vacío —el caso normal— hace que la resuelva
     * el backend con el catálogo a la fecha de la nota.
     */
    exchange_rate: string;
    notes: string;
    lines: PurchaseCreditNoteLineRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/**
 * El proveedor de la nota que se edita, con la etiqueta que trae su Resource:
 * así el select lo muestra desde el primer render.
 */
function supplierSeed(note?: PurchaseCreditNote): AjaxOption | null {
    if (!note?.supplier_id) {
        return null;
    }

    const name = note.supplier_name ?? '';

    return {
        value: note.supplier_id,
        label: note.supplier_code ? `${note.supplier_code} · ${name}` : name,
    };
}

/** La factura que la nota corrige, si corrige alguna. */
function invoiceSeed(note?: PurchaseCreditNote): AjaxOption | null {
    if (!note?.purchase_invoice_id) {
        return null;
    }

    const code = note.purchase_invoice_code ?? 'Factura de compra';

    return {
        value: note.purchase_invoice_id,
        label: note.purchase_invoice_number
            ? `${code} · ${note.purchase_invoice_number}`
            : code,
    };
}

/** La devolución que origina la nota, si nace de una. */
function returnSeed(note?: PurchaseCreditNote): AjaxOption | null {
    if (!note?.purchase_return_id) {
        return null;
    }

    return {
        value: note.purchase_return_id,
        label: note.purchase_return_code ?? 'Devolución de compra',
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
    line: PurchaseCreditNoteLineRow,
    tax: TaxOption | undefined,
): PurchaseCreditNoteLineRow {
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

/** La unidad en la que se acredita por defecto: la base del artículo. */
function baseUnitId(item: ItemCatalogEntry | undefined): string {
    if (!item) {
        return '';
    }

    const base = item.units.find((unit) => unit.is_base === 'yes');

    return (
        base?.measurement_unit_id ?? item.units[0]?.measurement_unit_id ?? ''
    );
}

export interface PurchaseCreditNoteTotals {
    /** Cantidad por precio, antes de cualquier rebaja. */
    gross: number;
    discountAmount: number;
    subtotal: number;
    taxAmount: number;
    /** Parte del impuesto que se entera al fisco en vez de acreditarse. */
    withholdingAmount: number;
    total: number;
}

function emptyLine(): PurchaseCreditNoteLineRow {
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
        warehouse_id: '',
        notes: '',
    };
}

/**
 * Solo se editan las líneas activas: las inactivas se conservan en la base por
 * la política de no borrado, pero no vuelven al formulario.
 */
function lineRows(note?: PurchaseCreditNote): PurchaseCreditNoteLineRow[] {
    const rows = (note?.lines ?? [])
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
            warehouse_id: line.warehouse_id ?? '',
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine()];
}

/**
 * Espejo del cálculo del backend (`PurchaseCreditNoteLineData`): sirve para
 * mostrar el resumen mientras se captura. El importe que se guarda siempre lo
 * recalcula el servidor.
 */
export function lineAmounts(line: PurchaseCreditNoteLineRow): {
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

export function usePurchaseCreditNoteForm({
    mode,
    options,
    initialData,
    onSuccess,
}: UsePurchaseCreditNoteFormProps) {
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
     * porque de su `meta` salen las líneas que la nota copia para acreditar, y
     * eso tiene que estar también al abrir una nota ya guardada.
     */
    const invoice = useRemoteOption({
        url: purchaseInvoices.lookup(companyId).url,
        seed: invoiceSeed(initialData),
        hydrate: true,
    });

    /**
     * Y las devoluciones contra el suyo, acotadas al proveedor elegido. Elegir
     * una es lo que deja la devolución acreditada al guardar la nota.
     */
    const purchaseReturn = useRemoteOption({
        url: purchaseReturns.lookup(companyId).url,
        seed: returnSeed(initialData),
        hydrate: true,
    });

    const initialCurrency =
        initialData?.currency ?? configuration?.base_currency ?? '';

    /**
     * Con la corrección permitida el campo nace con la tasa del catálogo a la
     * vista, no vacío: el usuario ve con qué se va a valorar la nota antes de
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
        useForm<PurchaseCreditNoteFormData>({
            id: initialData?.id ?? generateUUID(),
            supplier_id: initialData?.supplier_id ?? '',
            purchase_invoice_id: initialData?.purchase_invoice_id ?? '',
            purchase_return_id: initialData?.purchase_return_id ?? '',
            supplier_document_number:
                initialData?.supplier_document_number ?? '',
            note_date:
                initialData?.note_date ?? new Date().toISOString().slice(0, 10),
            reason: initialData?.reason ?? 'return',
            reason_detail: initialData?.reason_detail ?? '',
            /** Una nota nace en la moneda en la que la empresa lleva sus cifras. */
            currency: initialCurrency,
            exchange_rate: catalogRate(initialCurrency),
            notes: initialData?.notes ?? '',
            lines: lineRows(initialData),
        });

    /**
     * El catálogo de artículos ya no viaja en las props: la pantalla solo
     * conoce los que trae la nota y los que el usuario va eligiendo.
     *
     * La semilla se recalcula con las líneas vigentes, no solo con las que
     * trajo el documento: copiar las líneas de la factura mete artículos que la
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
     * Cambiar la moneda de la nota trae la tasa del catálogo de esa moneda. Los
     * precios ya capturados no se tocan: son los que acordó el proveedor.
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

        purchaseReturn.select(null);

        setData((current) => ({
            ...current,
            supplier_id: option?.value ?? '',
            purchase_invoice_id: '',
            purchase_return_id: '',
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
     * Elegir la factura copia su moneda, pero no sus líneas: lo que se acredita
     * casi nunca es la factura entera. Para traerlas está `copyInvoiceLines`.
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

    /**
     * Elegir la devolución copia su moneda y fija el motivo: una nota que nace
     * de una devolución es, por definición, una nota por devolución. La factura
     * afectada se elige aparte, porque una devolución puede no venir de ninguna.
     */
    const selectReturn = (option: AjaxOption | null) => {
        purchaseReturn.select(option);

        const meta = (option?.meta ?? {}) as Partial<PurchaseReturnOptionMeta>;

        setData((current) => ({
            ...current,
            purchase_return_id: option?.value ?? '',
            reason: option ? 'return' : current.reason,
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

    /**
     * Trae las líneas de la factura al formulario, cada una ya apuntando a la
     * suya. Sustituye lo capturado: es el punto de partida de la nota, no un
     * añadido, y desde aquí el usuario ajusta cantidades o quita lo que no
     * devuelve.
     */
    const copyInvoiceLines = () => {
        if (invoiceLines.length === 0) {
            return;
        }

        setData(
            'lines',
            invoiceLines.map((line) => ({
                id: generateUUID(),
                item_id: line.item_id,
                measurement_unit_id: line.measurement_unit_id,
                quantity: Number(line.quantity),
                unit_price: Number(line.unit_price),
                discount_percent: Number(line.discount_percent),
                tax_id: line.tax_id ?? '',
                tax_percent: Number(line.tax_percent),
                withholding_percent: Number(line.withholding_percent),
                purchase_invoice_line_id: line.id,
                warehouse_id: line.warehouse_id ?? '',
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

    const updateLine = <K extends keyof PurchaseCreditNoteLineRow>(
        index: number,
        field: K,
        value: PurchaseCreditNoteLineRow[K],
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
     * Cambiar de artículo invalida la unidad elegida y la trazabilidad a la
     * línea de factura: ya no es la misma mercancía.
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

    /**
     * Atar una línea a la de la factura copia lo que se facturó: artículo,
     * unidad, precio y sus cargos. Es lo que hace que el backend pueda
     * comprobar que no se acredita más de lo facturado.
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
                    warehouse_id:
                        line.warehouse_id || (source.warehouse_id ?? ''),
                };
            }),
        );
    };

    const totals: PurchaseCreditNoteTotals = data.lines.reduce(
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
         * si el usuario escribió otra: así una nota con fecha anterior se sigue
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
            post(purchaseCreditNotes.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                purchaseCreditNotes.update({
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
        returnLookupUrl: purchaseReturn.url,
        returnOption: purchaseReturn.optionOf(data.purchase_return_id),
        selectReturn,
        invoiceLines,
        copyInvoiceLines,
        selectCurrency,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineTax,
        setLineInvoiceLine,
        catalog,
    };
}
