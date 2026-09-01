import { useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import { useConfiguration } from '@/hooks/use-configuration';
import {
    useItemCatalog,
    type ItemCatalogEntry,
} from '@/hooks/use-item-catalog';
import type { ItemCatalogSeed } from '@/hooks/use-item-catalog';
import { useRemoteOption } from '@/hooks/use-remote-option';
import { useTodayRates } from '@/hooks/use-today-rates';
import { generateUUID } from '@/lib/utils';
import entries from '@/routes/entries';
import purchaseOrders from '@/routes/purchase-orders';
import suppliers from '@/routes/suppliers';
import { taxWithholdingPercent, type TaxOption } from '@/types/tax';
import {
    INITIAL_TYPE,
    PURCHASE_ORDER,
    PURCHASE_ORDER_LINE,
    SUPPLIER_TYPE,
    type Entry,
    type EntryInspectionStatus,
    type EntryOptions,
    type EntryType,
    type PurchaseOrderOptionLine,
    type PurchaseOrderOptionMeta,
    type SupplierOptionMeta,
} from '../types/Entry';

interface UseEntryFormProps {
    mode: 'create' | 'edit';
    options: EntryOptions;
    initialData?: Entry;
    onSuccess?: () => void;
}

export interface EntryLineRow {
    id: string;
    item_id: string;
    measurement_unit_id: string;
    /** Lo que llegó, y de eso lo que la inspección rechazó. */
    quantity: number;
    rejected_quantity: number;
    rejection_reason: string;
    unit_price: number;
    discount_percent: number;
    /** Impuesto del catálogo. De él salen los dos porcentajes de abajo. */
    tax_id: string;
    tax_percent: number;
    withholding_percent: number;
    /** Línea de la orden que esta línea recibe; vacía en una suelta. */
    sourceable_type: string;
    sourceable_id: string;
    /** Vacía deja que el kardex tome la ubicación por defecto de la bodega. */
    location_id: string;
    /** Lote del proveedor: al confirmar se crea si no existía. */
    lot_number: string;
    expires_at: string;
    /** Una serie por unidad aceptada, en artículos serializados. */
    serial_numbers: string[];
    notes: string;
}

interface EntryFormData {
    id: string;
    supplier_id: string;
    /** Documento origen; vacío en una entrada sin documento previo. */
    sourceable_type: string;
    sourceable_id: string;
    warehouse_id: string;
    entry_date: string;
    entry_type: EntryType;
    supplier_document: string;
    carrier: string;
    tracking_number: string;
    received_by: string;
    inspected_by: string;
    inspection_status: EntryInspectionStatus;
    currency: string;
    /**
     * Corrección manual de la tasa. Vacío —el caso normal— hace que la resuelva
     * el backend con el catálogo a la fecha de la entrada.
     */
    exchange_rate: string;
    /** Gastos capitalizables: se reparten entre las líneas, no se facturan. */
    freight_amount: number;
    other_charges: number;
    notes: string;
    lines: EntryLineRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/**
 * El proveedor de la entrada que se edita, con la etiqueta que trae su
 * Resource: así el select lo muestra desde el primer render.
 */
function supplierSeed(model?: Entry): AjaxOption | null {
    if (!model?.supplier_id) {
        return null;
    }

    const name = model.supplier_name ?? '';

    return {
        value: model.supplier_id,
        label: model.supplier_code ? `${model.supplier_code} · ${name}` : name,
    };
}

/** La orden de la que salió la mercancía, si salió de alguna. */
function orderSeed(model?: Entry): AjaxOption | null {
    if (!model?.sourceable_id) {
        return null;
    }

    return {
        value: model.sourceable_id,
        label: model.sourceable_code ?? 'Orden de compra',
    };
}

/** En compras el artículo se reconoce por su código. */
function itemLabel(entry: ItemCatalogEntry): string {
    return entry.code ? `${entry.code} — ${entry.name}` : entry.name;
}

/**
 * La línea con un impuesto del catálogo aplicado: sus dos porcentajes salen de
 * ahí y ya no se capturan a mano. Sin impuesto, ambos vuelven a cero.
 */
function withTax(line: EntryLineRow, tax: TaxOption | undefined): EntryLineRow {
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

/** La unidad en la que se recibe por defecto: la base del artículo. */
function baseUnitId(item: ItemCatalogEntry | undefined): string {
    if (!item) {
        return '';
    }

    const base = item.units.find((unit) => unit.is_base === 'yes');

    return (
        base?.measurement_unit_id ?? item.units[0]?.measurement_unit_id ?? ''
    );
}

export interface EntryTotals {
    /** Cantidad por costo, antes de cualquier rebaja. */
    gross: number;
    discountAmount: number;
    subtotal: number;
    taxAmount: number;
    withholdingAmount: number;
    total: number;
    /** Unidades aceptadas y su valor ya con los gastos repartidos. */
    receivedValue: number;
    landedTotal: number;
}

function emptyLine(): EntryLineRow {
    return {
        id: generateUUID(),
        item_id: '',
        measurement_unit_id: '',
        quantity: 1,
        rejected_quantity: 0,
        rejection_reason: '',
        unit_price: 0,
        discount_percent: 0,
        tax_id: '',
        tax_percent: 0,
        withholding_percent: 0,
        sourceable_type: '',
        sourceable_id: '',
        location_id: '',
        lot_number: '',
        expires_at: '',
        serial_numbers: [],
        notes: '',
    };
}

/**
 * Solo se editan las líneas activas: las inactivas se conservan en la base por
 * la política de no borrado, pero no vuelven al formulario.
 */
function lineRows(model?: Entry): EntryLineRow[] {
    const rows = (model?.lines ?? [])
        .filter((line) => line.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((line) => ({
            id: line.id,
            item_id: line.item_id,
            measurement_unit_id: line.measurement_unit_id,
            quantity: Number(line.quantity),
            rejected_quantity: Number(line.rejected_quantity),
            rejection_reason: line.rejection_reason ?? '',
            unit_price: Number(line.unit_price),
            discount_percent: Number(line.discount_percent),
            tax_id: line.tax_id ?? '',
            tax_percent: Number(line.tax_percent),
            withholding_percent: Number(line.withholding_percent),
            sourceable_type: line.sourceable_type ?? '',
            sourceable_id: line.sourceable_id ?? '',
            location_id: line.location_id ?? '',
            lot_number: line.lot_number ?? '',
            expires_at: line.expires_at ?? '',
            serial_numbers: line.serial_numbers ?? [],
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine()];
}

function round2(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

/**
 * Espejo del cálculo del backend (`EntryLineData`): sirve para mostrar el
 * resumen mientras se captura. El importe que se guarda siempre lo recalcula el
 * servidor.
 */
export function lineAmounts(line: EntryLineRow): {
    gross: number;
    discountAmount: number;
    subtotal: number;
    taxAmount: number;
    withholdingAmount: number;
    total: number;
    /** Lo aceptado es lo que llegó menos lo rechazado. */
    receivedQuantity: number;
    /** Valor de lo aceptado, que es sobre lo que se reparten los gastos. */
    receivedValue: number;
} {
    const gross = line.quantity * line.unit_price;
    const discountAmount = round2((gross * line.discount_percent) / 100);
    const subtotal = round2(gross - discountAmount);
    const taxAmount = round2((subtotal * line.tax_percent) / 100);

    const receivedQuantity = Math.max(
        round2(line.quantity - line.rejected_quantity),
        0,
    );

    return {
        gross: round2(gross),
        discountAmount,
        subtotal,
        taxAmount,
        /** La retención se practica sobre el impuesto, no sobre la base. */
        withholdingAmount: round2((taxAmount * line.withholding_percent) / 100),
        total: round2(subtotal + taxAmount),
        receivedQuantity,
        receivedValue:
            line.quantity > 0
                ? round2((subtotal * receivedQuantity) / line.quantity)
                : 0,
    };
}

export function useEntryForm({
    mode,
    options,
    initialData,
    onSuccess,
}: UseEntryFormProps) {
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
     * Y las órdenes contra el suyo, acotadas al proveedor elegido. Se hidrata
     * porque de su `meta` salen las líneas que la entrada copia, y eso tiene
     * que estar también al abrir una entrada ya guardada.
     */
    const order = useRemoteOption({
        url: purchaseOrders.lookup(companyId).url,
        seed: orderSeed(initialData),
        hydrate: true,
    });

    const initialCurrency =
        initialData?.currency ?? configuration?.base_currency ?? '';

    /**
     * Con la corrección permitida el campo nace con la tasa del catálogo a la
     * vista, no vacío: el usuario ve con qué se va a valorar la entrada antes
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
        useForm<EntryFormData>({
            id: initialData?.id ?? generateUUID(),
            supplier_id: initialData?.supplier_id ?? '',
            sourceable_type: initialData?.sourceable_type ?? '',
            sourceable_id: initialData?.sourceable_id ?? '',
            warehouse_id: initialData?.warehouse_id ?? '',
            entry_date:
                initialData?.entry_date ??
                new Date().toISOString().slice(0, 10),
            entry_type: initialData?.entry_type ?? 'purchase',
            supplier_document: initialData?.supplier_document ?? '',
            carrier: initialData?.carrier ?? '',
            tracking_number: initialData?.tracking_number ?? '',
            received_by: initialData?.received_by ?? '',
            inspected_by: initialData?.inspected_by ?? '',
            inspection_status: initialData?.inspection_status ?? 'pending',
            /** Una entrada nace en la moneda en la que la empresa lleva sus cifras. */
            currency: initialCurrency,
            exchange_rate: catalogRate(initialCurrency),
            freight_amount: Number(initialData?.freight_amount ?? 0),
            other_charges: Number(initialData?.other_charges ?? 0),
            notes: initialData?.notes ?? '',
            lines: lineRows(initialData),
        });

    /**
     * El catálogo de artículos ya no viaja en las props: la pantalla solo
     * conoce los que trae la entrada y los que el usuario va eligiendo.
     *
     * La semilla se recalcula con las líneas vigentes, no solo con las que trajo
     * el documento: copiar las líneas de la orden mete artículos que la pantalla
     * nunca había visto y el hook los hidrata solo.
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
     * Cambiar la moneda trae la tasa del catálogo de esa moneda. Los costos ya
     * capturados no se tocan: son los que el proveedor facturó.
     */
    const selectCurrency = (currency: string) =>
        setData((current) => ({
            ...current,
            currency,
            exchange_rate: catalogRate(currency),
        }));

    /**
     * Cambiar de proveedor invalida la orden elegida, que era de otro, y con
     * ella la trazabilidad de las líneas. La moneda sí se copia: la orden y la
     * entrada se valoran en la que el proveedor factura.
     */
    const selectSupplier = (option: AjaxOption | null) => {
        supplier.select(option);
        order.select(null);

        const meta = (option?.meta ?? {}) as Partial<SupplierOptionMeta>;

        setData((current) => ({
            ...current,
            supplier_id: option?.value ?? '',
            sourceable_type: '',
            sourceable_id: '',
            lines: current.lines.map((line) => ({
                ...line,
                sourceable_type: '',
                sourceable_id: '',
            })),
        }));

        if (meta.currency) {
            selectCurrency(meta.currency);
        }
    };

    /**
     * Elegir la orden origen copia lo que la entrada hereda de ella —bodega y
     * moneda—, pero no sus líneas: casi nunca llega la orden entera de una vez.
     * Para traerlas está `copyOrderLines`.
     */
    const selectOrder = (option: AjaxOption | null) => {
        order.select(option);

        const meta = (option?.meta ?? {}) as Partial<PurchaseOrderOptionMeta>;

        setData((current) => ({
            ...current,
            sourceable_type: option ? PURCHASE_ORDER : '',
            sourceable_id: option?.value ?? '',
            warehouse_id: meta.warehouse_id ?? current.warehouse_id,
            /** Sin orden no hay contra qué trazar las líneas. */
            lines: current.lines.map((line) => ({
                ...line,
                sourceable_type: option ? line.sourceable_type : '',
                sourceable_id: option ? line.sourceable_id : '',
            })),
        }));

        if (meta.currency) {
            selectCurrency(meta.currency);
        }
    };

    /** Las líneas de la orden elegida, tal como llegan en el `meta`. */
    const orderLines: PurchaseOrderOptionLine[] =
        (
            (order.optionOf(data.sourceable_id)?.meta ??
                {}) as Partial<PurchaseOrderOptionMeta>
        ).lines ?? [];

    const orderLineOf = (id: string): PurchaseOrderOptionLine | undefined =>
        id ? orderLines.find((line) => line.id === id) : undefined;

    /** Cuánto queda por recibir de una línea de la orden. */
    const pendingOf = (orderLineId: string): number => {
        const source = orderLineOf(orderLineId);

        if (!source) {
            return 0;
        }

        return round2(
            Number(source.quantity) - Number(source.received_quantity),
        );
    };

    /**
     * Trae las líneas de la orden al formulario, cada una ya apuntando a la
     * suya y con lo que aún queda por llegar. Sustituye lo capturado: es el
     * punto de partida de la entrada, no un añadido.
     *
     * Lo ya recibido por completo no vuelve: no hay nada que esperar de ahí.
     */
    const copyOrderLines = () => {
        const pending = orderLines.filter(
            (line) =>
                Number(line.quantity) - Number(line.received_quantity) > 0,
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
                    Number(line.quantity) - Number(line.received_quantity),
                ),
                rejected_quantity: 0,
                rejection_reason: '',
                unit_price: Number(line.unit_price),
                discount_percent: Number(line.discount_percent),
                tax_id: line.tax_id ?? '',
                tax_percent: Number(line.tax_percent),
                withholding_percent: Number(line.withholding_percent),
                sourceable_type: PURCHASE_ORDER_LINE,
                sourceable_id: line.id,
                location_id: '',
                lot_number: '',
                expires_at: '',
                serial_numbers: [],
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

    const updateLine = <K extends keyof EntryLineRow>(
        index: number,
        field: K,
        value: EntryLineRow[K],
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
     * Cambiar de artículo invalida la unidad, la trazabilidad a la orden y todo
     * lo que identificaba a la mercancía anterior: ya no es la misma.
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
                              sourceable_type: '',
                              sourceable_id: '',
                              lot_number: '',
                              expires_at: '',
                              serial_numbers: [],
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
     * Atar una línea a la de la orden copia lo que se pidió: artículo, unidad,
     * costo y sus cargos. Es lo que hace que el backend pueda comprobar que no
     * llega más de lo pedido.
     */
    const setLineOrderLine = (index: number, orderLineId: string) => {
        const source = orderLineOf(orderLineId);

        setData(
            'lines',
            data.lines.map((line, i) => {
                if (i !== index) {
                    return line;
                }

                if (!source) {
                    return { ...line, sourceable_type: '', sourceable_id: '' };
                }

                return {
                    ...line,
                    sourceable_type: PURCHASE_ORDER_LINE,
                    sourceable_id: source.id,
                    item_id: source.item_id,
                    /** La línea se recibe en la unidad en la que se pidió. */
                    measurement_unit_id: source.measurement_unit_id,
                    unit_price: Number(source.unit_price),
                    discount_percent: Number(source.discount_percent),
                    tax_id: source.tax_id ?? '',
                    tax_percent: Number(source.tax_percent),
                    withholding_percent: Number(source.withholding_percent),
                };
            }),
        );
    };

    /** Las ubicaciones de la bodega elegida: la mercancía entra a una suya. */
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

    /**
     * El inventario inicial no tiene proveedor ni documento origen: elegirlo
     * suelta los dos, igual que el backend los rechazaría.
     */
    const selectEntryType = (entryType: EntryType) => {
        if (entryType !== INITIAL_TYPE) {
            setData('entry_type', entryType);

            return;
        }

        supplier.select(null);
        order.select(null);

        setData((current) => ({
            ...current,
            entry_type: entryType,
            supplier_id: '',
            sourceable_type: '',
            sourceable_id: '',
            lines: current.lines.map((line) => ({
                ...line,
                sourceable_type: '',
                sourceable_id: '',
            })),
        }));
    };

    const totals: EntryTotals = data.lines.reduce(
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
                receivedValue: round2(
                    accumulator.receivedValue + amounts.receivedValue,
                ),
                landedTotal: 0,
            };
        },
        {
            gross: 0,
            discountAmount: 0,
            subtotal: 0,
            taxAmount: 0,
            withholdingAmount: 0,
            total: 0,
            receivedValue: 0,
            landedTotal: 0,
        },
    );

    /** Valor ingresado: lo aceptado más los gastos que se le suman al costo. */
    totals.landedTotal = round2(
        totals.receivedValue +
            Number(data.freight_amount) +
            Number(data.other_charges),
    );

    /**
     * Cuánto suben los gastos el costo de cada unidad, en tanto por ciento.
     * Es la misma proporción que aplica el backend al repartirlos por valor.
     */
    const landedRatio =
        totals.receivedValue > 0
            ? round2(
                  ((Number(data.freight_amount) + Number(data.other_charges)) *
                      100) /
                      totals.receivedValue,
              )
            : 0;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        /**
         * El campo enseña la tasa del catálogo, pero solo viaja como corrección
         * si el usuario escribió otra: así una entrada con fecha anterior se
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
            post(entries.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                entries.update({ company: companyId, id: initialData.id }).url,
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
        landedRatio,
        supplierLookupUrl: supplier.url,
        supplierOption: supplier.optionOf(data.supplier_id),
        selectSupplier,
        orderLookupUrl: order.url,
        orderOption: order.optionOf(data.sourceable_id),
        selectOrder,
        orderLines,
        pendingOf,
        copyOrderLines,
        selectCurrency,
        selectWarehouse,
        selectEntryType,
        locations,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineTax,
        setLineOrderLine,
        catalog,
        /** El inventario inicial no admite proveedor ni orden de origen. */
        allowsSupplier: data.entry_type !== INITIAL_TYPE,
        requiresSupplier: data.entry_type === SUPPLIER_TYPE,
    };
}
