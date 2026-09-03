import { useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import { useConfiguration } from '@/hooks/use-configuration';
import {
    useItemCatalog,
    type ItemCatalogEntry,
} from '@/hooks/use-item-catalog';
import type { ItemCatalogSeed } from '@/hooks/use-item-catalog';
import {
    usePendingOrderLines,
    type PendingOrderLine,
} from '@/hooks/use-pending-order-lines';
import { useRemoteOption } from '@/hooks/use-remote-option';
import { useTodayRates } from '@/hooks/use-today-rates';
import { generateUUID } from '@/lib/utils';
import entries from '@/routes/entries';
import purchaseOrders from '@/routes/purchase-orders';
import suppliers from '@/routes/suppliers';
import {
    INITIAL_TYPE,
    PURCHASE_ORDER,
    PURCHASE_ORDER_LINE,
    SUPPLIER_TYPE,
    type Entry,
    type EntryInspectionStatus,
    type EntryLine,
    type EntryOptions,
    type EntryType,
    type PurchaseOrderOptionMeta,
    type SupplierOptionMeta,
} from '../types/Entry';

interface UseEntryFormProps {
    mode: 'create' | 'edit';
    options: EntryOptions;
    initialData?: Entry;
    onSuccess?: () => void;
}

/**
 * Uno de los lotes con los que llega la línea. El número es lo único que se
 * captura: el lote del maestro lo resuelve el backend al confirmar.
 */
export interface EntryLineLotRow {
    id: string;
    lot_number: string;
    expires_at: string;
    quantity: number;
    status: 'active' | 'inactive';
}

/**
 * Una de las unidades con serie que llegan en la línea. `lot_number` dice de
 * cuál de sus lotes sale, cuando la línea lleva más de uno.
 */
export interface EntryLineSerialRow {
    id: string;
    serial_number: string;
    lot_number: string;
    status: 'active' | 'inactive';
}

/**
 * La pantalla de la entrada solo captura cantidad y trazabilidad: el costo, el
 * impuesto y el descuento se deciden en la orden de compra y los pone el
 * backend, así que no viajan en la fila.
 */
export interface EntryLineRow {
    id: string;
    item_id: string;
    measurement_unit_id: string;
    /** Lo que se recibe, y de eso lo que la inspección rechazó. */
    quantity: number;
    rejected_quantity: number;
    rejection_reason: string;
    /** Línea de la orden que esta línea recibe; vacía en una suelta. */
    sourceable_type: string;
    sourceable_id: string;
    /** Vacía deja que el kardex tome la ubicación por defecto de la bodega. */
    location_id: string;
    lots: EntryLineLotRow[];
    serials: EntryLineSerialRow[];
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
    lines: number;
    /** Unidades aceptadas y rechazadas, en las unidades de cada línea. */
    receivedQuantity: number;
    rejectedQuantity: number;
}

/**
 * ¿Esa fila de trazabilidad ya está guardada? Una que nunca llegó a la base se
 * puede quitar sin más; una que sí, se desactiva.
 */
function isSaved(
    id: string,
    model: Entry | undefined,
    collection: 'lots' | 'serials',
): boolean {
    return (model?.lines ?? []).some((line) =>
        (line[collection] ?? []).some((row) => row.id === id),
    );
}

function emptyLine(): EntryLineRow {
    return {
        id: generateUUID(),
        item_id: '',
        measurement_unit_id: '',
        quantity: 1,
        rejected_quantity: 0,
        rejection_reason: '',
        sourceable_type: '',
        sourceable_id: '',
        location_id: '',
        lots: [],
        serials: [],
        notes: '',
    };
}

/** Los lotes activos de una línea guardada, en el orden en que se capturaron. */
function lotRows(line: EntryLine): EntryLineLotRow[] {
    return (line.lots ?? [])
        .filter((lot) => lot.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((lot) => ({
            id: lot.id,
            lot_number: lot.lot_number,
            expires_at: lot.expires_at ?? '',
            quantity: Number(lot.quantity),
            status: 'active' as const,
        }));
}

/** Las series activas de una línea guardada, cada una atada a su lote. */
function serialRows(line: EntryLine): EntryLineSerialRow[] {
    const lotNumberOf = new Map(
        (line.lots ?? []).map((lot) => [lot.id, lot.lot_number]),
    );

    return (line.serials ?? [])
        .filter((serial) => serial.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((serial) => ({
            id: serial.id,
            serial_number: serial.serial_number,
            lot_number: serial.entry_line_lot_id
                ? (lotNumberOf.get(serial.entry_line_lot_id) ?? '')
                : '',
            status: 'active' as const,
        }));
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
            sourceable_type: line.sourceable_type ?? '',
            sourceable_id: line.sourceable_id ?? '',
            location_id: line.location_id ?? '',
            lots: lotRows(line),
            serials: serialRows(line),
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine()];
}

function round2(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

/**
 * Lo aceptado de una línea: lo que se recibe menos lo que la inspección
 * rechaza. Es lo único que llega al inventario, y la única cuenta que la
 * pantalla necesita hacer: el dinero lo pone el backend.
 */
export function acceptedQuantity(line: EntryLineRow): number {
    return Math.max(round2(line.quantity - line.rejected_quantity), 0);
}

/** Cuánto de la línea se repartió ya en lotes. */
export function assignedToLots(line: EntryLineRow): number {
    return round2(
        line.lots
            .filter((lot) => lot.status === 'active')
            .reduce((total, lot) => total + lot.quantity, 0),
    );
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

    /** Y las órdenes contra el suyo, acotadas al proveedor elegido. */
    const order = useRemoteOption({
        url: purchaseOrders.lookup(companyId).url,
        seed: orderSeed(initialData),
    });

    /**
     * Lo que a la orden elegida le queda por recibir, en su propia petición.
     * Al abrir una entrada guardada se pide sola, conservando las líneas que
     * esa entrada ya tenía atadas aunque su saldo esté en cero.
     */
    const pendingLines = usePendingOrderLines({
        urlFor: (orderId, ids) =>
            purchaseOrders.receivableLines(
                { company: companyId, id: orderId },
                { query: { ids } },
            ).url,
        initialOrderId: initialData?.sourceable_id ?? '',
        initialLineIds: (initialData?.lines ?? [])
            .filter((line) => line.status === 'active')
            .map((line) => line.sourceable_id ?? ''),
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
     * Elegir la orden origen arma la entrada con lo que le queda por llegar: su
     * cabecera —bodega y moneda— y sus líneas pendientes, cada una apuntando a
     * la suya. Sustituye lo capturado: es el punto de partida de la entrada, no
     * un añadido.
     *
     * Lo ya recibido por completo no vuelve: no hay nada que esperar de ahí. Y
     * la cantidad queda editable, porque lo que manda es lo que de verdad entró
     * al muelle, no lo que se pidió.
     *
     * Las líneas se piden aparte y no viajan en el `meta` del select: el saldo
     * solo interesa de la orden elegida.
     */
    const selectOrder = async (option: AjaxOption | null) => {
        order.select(option);

        if (!option) {
            pendingLines.clear();

            setData((current) => ({
                ...current,
                sourceable_type: '',
                sourceable_id: '',
                /** Sin orden no hay contra qué trazar las líneas. */
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

        const pending = await pendingLines.fetchLines(option.value);

        /** Una orden sin saldo deja intacto lo que ya se había capturado. */
        if (pending.length === 0) {
            return;
        }

        setData(
            'lines',
            pending.map((line) => ({
                id: generateUUID(),
                item_id: line.item_id,
                measurement_unit_id: line.measurement_unit_id,
                quantity: round2(Number(line.pending_quantity)),
                rejected_quantity: 0,
                rejection_reason: '',
                sourceable_type: PURCHASE_ORDER_LINE,
                sourceable_id: line.id,
                location_id: '',
                lots: [],
                serials: [],
                notes: '',
            })),
        );
    };

    /** Las líneas de la orden elegida con su saldo, tal como las trajo el servidor. */
    const orderLines = pendingLines.lines;

    const orderLineOf = (id: string): PendingOrderLine | undefined =>
        id ? orderLines.find((line) => line.id === id) : undefined;

    /** Cuánto queda por recibir de una línea de la orden. */
    const pendingOf = (orderLineId: string): number =>
        round2(Number(orderLineOf(orderLineId)?.pending_quantity ?? 0));

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
                    ? {
                          ...line,
                          item_id: item?.id ?? '',
                          measurement_unit_id: baseUnitId(item),
                          sourceable_type: '',
                          sourceable_id: '',
                          lots: [],
                          serials: [],
                      }
                    : line,
            ),
        );
    };

    /**
     * Atar una línea a la de la orden copia lo que se pidió: artículo y unidad.
     * Es lo que hace que el backend pueda comprobar que no llega más de lo
     * pedido —y de donde saca el costo con el que se valora—.
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

    /**
     * Cuánto entra al inventario, en las unidades de cada línea. La pantalla no
     * enseña importes: el costo lo pone el backend con la orden o con el
     * promedio del artículo, así que aquí no hay nada que sumar en dinero.
     */
    const totals: EntryTotals = data.lines.reduce(
        (accumulator, line) => ({
            lines: accumulator.lines + 1,
            receivedQuantity: round2(
                accumulator.receivedQuantity + acceptedQuantity(line),
            ),
            rejectedQuantity: round2(
                accumulator.rejectedQuantity + line.rejected_quantity,
            ),
        }),
        { lines: 0, receivedQuantity: 0, rejectedQuantity: 0 },
    );

    /** ---- Trazabilidad de la línea: lotes y series ---- */

    const mapLine = (
        index: number,
        change: (line: EntryLineRow) => EntryLineRow,
    ) =>
        setData(
            'lines',
            data.lines.map((line, i) => (i === index ? change(line) : line)),
        );

    const addLineLot = (index: number) =>
        mapLine(index, (line) => ({
            ...line,
            lots: [
                ...line.lots,
                {
                    id: generateUUID(),
                    lot_number: '',
                    expires_at: '',
                    /** Lo que falta por repartir: casi siempre es todo. */
                    quantity: Math.max(
                        round2(line.quantity - assignedToLots(line)),
                        0,
                    ),
                    status: 'active' as const,
                },
            ],
        }));

    const updateLineLot = <K extends keyof EntryLineLotRow>(
        index: number,
        lotIndex: number,
        field: K,
        value: EntryLineLotRow[K],
    ) =>
        mapLine(index, (line) => ({
            ...line,
            lots: line.lots.map((lot, i) =>
                i === lotIndex ? { ...lot, [field]: value } : lot,
            ),
        }));

    /**
     * Una fila que nunca se guardó desaparece; una que ya existe se desactiva.
     * La política de no borrado también alcanza a la trazabilidad.
     */
    const removeLineLot = (index: number, lotIndex: number) =>
        mapLine(index, (line) => {
            const lot = line.lots[lotIndex];

            if (!lot) {
                return line;
            }

            /** Las series que salían de ese lote se quedan sin lote. */
            const serials = line.serials.map((serial) =>
                serial.lot_number === lot.lot_number
                    ? { ...serial, lot_number: '' }
                    : serial,
            );

            return isSaved(lot.id, initialData, 'lots')
                ? {
                      ...line,
                      serials,
                      lots: line.lots.map((current, i) =>
                          i === lotIndex
                              ? { ...current, status: 'inactive' as const }
                              : current,
                      ),
                  }
                : {
                      ...line,
                      serials,
                      lots: line.lots.filter((_, i) => i !== lotIndex),
                  };
        });

    const addLineSerials = (index: number, numbers: string[]) =>
        mapLine(index, (line) => ({
            ...line,
            serials: [
                ...line.serials,
                ...numbers.map((serial_number) => ({
                    id: generateUUID(),
                    serial_number,
                    lot_number: '',
                    status: 'active' as const,
                })),
            ],
        }));

    const updateLineSerial = <K extends keyof EntryLineSerialRow>(
        index: number,
        serialIndex: number,
        field: K,
        value: EntryLineSerialRow[K],
    ) =>
        mapLine(index, (line) => ({
            ...line,
            serials: line.serials.map((serial, i) =>
                i === serialIndex ? { ...serial, [field]: value } : serial,
            ),
        }));

    const removeLineSerial = (index: number, serialIndex: number) =>
        mapLine(index, (line) => {
            const serial = line.serials[serialIndex];

            if (!serial) {
                return line;
            }

            return isSaved(serial.id, initialData, 'serials')
                ? {
                      ...line,
                      serials: line.serials.map((current, i) =>
                          i === serialIndex
                              ? { ...current, status: 'inactive' as const }
                              : current,
                      ),
                  }
                : {
                      ...line,
                      serials: line.serials.filter((_, i) => i !== serialIndex),
                  };
        });

    /** Lo que pidió la línea de la orden. Vacío en una línea suelta. */
    const orderedQuantityOf = (line: EntryLineRow): number | null => {
        const source = orderLineOf(line.sourceable_id);

        return source ? Number(source.quantity) : null;
    };

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
        supplierLookupUrl: supplier.url,
        supplierOption: supplier.optionOf(data.supplier_id),
        selectSupplier,
        orderLookupUrl: order.url,
        orderOption: order.optionOf(data.sourceable_id),
        selectOrder,
        orderLines,
        pendingOf,
        loadingOrderLines: pendingLines.loading,
        orderLinesFailed: pendingLines.failed,
        selectCurrency,
        selectWarehouse,
        selectEntryType,
        locations,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineOrderLine,
        addLineLot,
        updateLineLot,
        removeLineLot,
        addLineSerials,
        updateLineSerial,
        removeLineSerial,
        orderedQuantityOf,
        catalog,
        /** El inventario inicial no admite proveedor ni orden de origen. */
        allowsSupplier: data.entry_type !== INITIAL_TYPE,
        requiresSupplier: data.entry_type === SUPPLIER_TYPE,
    };
}
