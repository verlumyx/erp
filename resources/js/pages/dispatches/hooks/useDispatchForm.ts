import { useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import { useConfiguration } from '@/hooks/use-configuration';
import {
    useItemCatalog,
    type ItemCatalogEntry,
    type ItemCatalogSeed,
} from '@/hooks/use-item-catalog';
import {
    usePendingOrderLines,
    type PendingOrderLine,
} from '@/hooks/use-pending-order-lines';
import { useRemoteOption } from '@/hooks/use-remote-option';
import {
    useRemoteOptionSet,
    type RemoteOptionSeed,
} from '@/hooks/use-remote-option-set';
import { generateUUID } from '@/lib/utils';
import clients from '@/routes/clients';
import dispatches from '@/routes/dispatches';
import itemLots from '@/routes/item-lots';
import itemSerials from '@/routes/item-serials';
import routes from '@/routes/routes';
import salesOrders from '@/routes/sales-orders';
import type {
    ClientAddressOption,
    ClientOptionMeta,
    Dispatch,
    DispatchLine,
    DispatchOptions,
    RouteOptionMeta,
    SalesOrderOptionMeta,
} from '../types/Dispatch';

interface UseDispatchFormProps {
    mode: 'create' | 'edit';
    options: DispatchOptions;
    initialData?: Dispatch;
    onSuccess?: () => void;
}

/** Alias del morph map del pedido de venta y de su línea. */
const SALES_ORDER = 'sales_order';
const SALES_ORDER_LINE = 'sales_order_line';

/** Uno de los lotes de los que sale la línea; siempre elegido del maestro. */
export interface DispatchLineLotRow {
    id: string;
    lot_id: string;
    quantity: number;
    status: 'active' | 'inactive';
}

/**
 * Una de las unidades con serie que salen en la línea. `lot_id` dice de cuál de
 * sus lotes sale, cuando la línea lleva más de uno.
 */
export interface DispatchLineSerialRow {
    id: string;
    serial_id: string;
    lot_id: string;
    status: 'active' | 'inactive';
}

/**
 * La pantalla del despacho solo captura cantidad y trazabilidad: el precio, el
 * impuesto y el descuento se deciden en el pedido de venta y los pone el
 * backend, así que no viajan en la fila.
 */
export interface DispatchLineRow {
    id: string;
    item_id: string;
    measurement_unit_id: string;
    quantity: number;
    /** Línea del pedido que esta línea despacha; vacía en una suelta. */
    sourceable_id: string;
    /** Vacía deja que el kardex tome la ubicación por defecto de la bodega. */
    location_id: string;
    lots: DispatchLineLotRow[];
    serials: DispatchLineSerialRow[];
    notes: string;
}

interface DispatchFormData {
    id: string;
    client_id: string;
    /** Pedido de origen; vacío en un despacho directo. */
    sourceable_type: string;
    sourceable_id: string;
    client_address_id: string;
    warehouse_id: string;
    /** Ruta por la que sale. La parada la escribe después la planificación. */
    route_id: string;
    dispatch_date: string;
    driver_id: string;
    vehicle_plate: string;
    carrier: string;
    tracking_number: string;
    freight_amount: number;
    notes: string;
    lines: DispatchLineRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/**
 * El cliente del despacho que se edita, con la etiqueta que trae su Resource:
 * así el select lo muestra desde el primer render.
 */
function clientSeed(model?: Dispatch): AjaxOption | null {
    /** La pantalla solo edita despachos a un cliente: uno de traslado no pasa por aquí. */
    if (model?.recipient_type !== 'client' || !model.recipient_id) {
        return null;
    }

    const name = model.recipient_name ?? '';

    return {
        value: model.recipient_id,
        label: model.recipient_code
            ? `${model.recipient_code} — ${name}`
            : name,
    };
}

/** La ruta a la que el despacho ya estaba asignado, si lo estaba. */
function routeSeed(model?: Dispatch): AjaxOption | null {
    if (!model?.route_id) {
        return null;
    }

    const name = model.route_name ?? '';

    return {
        value: model.route_id,
        label: model.route_code ? `${model.route_code} — ${name}` : name,
    };
}

/** El pedido del que salió la mercancía, si salió de alguno. */
function sourceSeed(model?: Dispatch): AjaxOption | null {
    if (!model?.sourceable_id) {
        return null;
    }

    return {
        value: model.sourceable_id,
        label: model.sourceable_code ?? 'Pedido de venta',
    };
}

/** En ventas el artículo se reconoce por su sku. */
function itemLabel(entry: ItemCatalogEntry): string {
    return entry.sku ? `${entry.sku} — ${entry.name}` : entry.name;
}

/**
 * La línea con un impuesto del catálogo aplicado: sus dos porcentajes salen de
 * ahí y ya no se capturan a mano. Sin impuesto, ambos vuelven a cero.
 */
/** La unidad en la que se despacha por defecto: la base del artículo. */
function baseUnitId(item: ItemCatalogEntry | undefined): string {
    if (!item) {
        return '';
    }

    const base = item.units.find((unit) => unit.is_base === 'yes');

    return (
        base?.measurement_unit_id ?? item.units[0]?.measurement_unit_id ?? ''
    );
}

export interface DispatchTotals {
    lines: number;
    /** Bultos de la carga: lo único que el despacho decide. */
    quantity: number;
}

function emptyLine(): DispatchLineRow {
    return {
        id: generateUUID(),
        item_id: '',
        measurement_unit_id: '',
        quantity: 1,
        sourceable_id: '',
        location_id: '',
        lots: [],
        serials: [],
        notes: '',
    };
}

/**
 * Solo se editan las líneas activas: las inactivas se conservan en la base por
 * la política de no borrado, pero no vuelven al formulario.
 */
function lineRows(model?: Dispatch): DispatchLineRow[] {
    const rows = (model?.lines ?? [])
        .filter((line) => line.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((line) => ({
            id: line.id,
            item_id: line.item_id,
            measurement_unit_id: line.measurement_unit_id,
            quantity: Number(line.quantity),
            sourceable_id: line.sourceable_id ?? '',
            location_id: line.location_id ?? '',
            lots: lotRows(line),
            serials: serialRows(line),
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine()];
}

/** Cuánto de la línea se repartió ya en lotes. */
export function assignedToLots(line: DispatchLineRow): number {
    return round2(
        line.lots
            .filter((lot) => lot.status === 'active')
            .reduce((total, lot) => total + lot.quantity, 0),
    );
}

/** Los lotes activos de una línea guardada. */
function lotRows(line: DispatchLine): DispatchLineLotRow[] {
    return (line.lots ?? [])
        .filter((lot) => lot.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((lot) => ({
            id: lot.id,
            lot_id: lot.lot_id,
            quantity: Number(lot.quantity),
            status: 'active' as const,
        }));
}

/** Las series activas de una línea guardada, cada una atada a su lote. */
function serialRows(line: DispatchLine): DispatchLineSerialRow[] {
    const lotIdOf = new Map(
        (line.lots ?? []).map((lot) => [lot.id, lot.lot_id]),
    );

    return (line.serials ?? [])
        .filter((serial) => serial.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((serial) => ({
            id: serial.id,
            serial_id: serial.serial_id,
            lot_id: serial.dispatch_line_lot_id
                ? (lotIdOf.get(serial.dispatch_line_lot_id) ?? '')
                : '',
            status: 'active' as const,
        }));
}

function round2(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

/**
 * ¿Esa fila de trazabilidad ya está guardada? Una que nunca llegó a la base se
 * puede quitar sin más; una que sí, se desactiva.
 */
function isSaved(
    id: string,
    model: Dispatch | undefined,
    collection: 'lots' | 'serials',
): boolean {
    return (model?.lines ?? []).some((line) =>
        (line[collection] ?? []).some((row) => row.id === id),
    );
}

export function useDispatchForm({
    mode,
    options,
    initialData,
    onSuccess,
}: UseDispatchFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const configuration = useConfiguration();

    /** Los importes del despacho son de costo: van en la moneda de la empresa. */
    const currency = configuration?.base_currency ?? 'USD';

    /** El padrón de clientes se busca contra su endpoint de opciones. */
    const client = useRemoteOption({
        url: clients.lookup(companyId).url,
        seed: clientSeed(initialData),
        hydrate: true,
    });

    /** Y los pedidos contra el suyo, acotados al cliente elegido. */
    const source = useRemoteOption({
        url: salesOrders.lookup(companyId).url,
        seed: sourceSeed(initialData),
        hydrate: true,
    });

    /**
     * Lo que al pedido elegido le queda por despachar, en su propia petición.
     * Al abrir un despacho guardado se pide solo, conservando las líneas que
     * ese despacho ya tenía atadas aunque su saldo esté en cero.
     */
    const pendingLines = usePendingOrderLines({
        urlFor: (orderId, ids) =>
            salesOrders.dispatchableLines(
                { company: companyId, id: orderId },
                { query: { ids } },
            ).url,
        initialOrderId: initialData?.sourceable_id ?? '',
        initialLineIds: (initialData?.lines ?? [])
            .filter((line) => line.status === 'active')
            .map((line) => line.sourceable_id ?? ''),
    });

    /**
     * Y las rutas contra el suyo. Se hidrata porque de su `meta` salen la
     * bodega, el conductor y la placa que el despacho estrena al elegirla.
     */
    const deliveryRoute = useRemoteOption({
        url: routes.lookup(companyId).url,
        seed: routeSeed(initialData),
        hydrate: true,
    });

    const { data, setData, post, put, transform, processing, errors, reset } =
        useForm<DispatchFormData>({
            id: initialData?.id ?? generateUUID(),
            client_id: initialData?.recipient_id ?? '',
            sourceable_type: initialData?.sourceable_type ?? '',
            sourceable_id: initialData?.sourceable_id ?? '',
            client_address_id: initialData?.client_address_id ?? '',
            warehouse_id: initialData?.warehouse_id ?? '',
            route_id: initialData?.route_id ?? '',
            dispatch_date:
                initialData?.dispatch_date ??
                new Date().toISOString().slice(0, 10),
            driver_id: initialData?.driver_id ?? '',
            vehicle_plate: initialData?.vehicle_plate ?? '',
            carrier: initialData?.carrier ?? '',
            tracking_number: initialData?.tracking_number ?? '',
            freight_amount: Number(initialData?.freight_amount ?? 0),
            notes: initialData?.notes ?? '',
            lines: lineRows(initialData),
        });

    /**
     * El catálogo de artículos ya no viaja en las props: la pantalla solo conoce
     * los que trae el despacho y los que el usuario va eligiendo.
     *
     * La semilla se recalcula con las líneas vigentes, no solo con las que trajo
     * el documento: copiar las líneas del pedido mete artículos que la pantalla
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

    /** Lotes y series elegidos en las líneas, aplanados de sus colecciones. */
    const lotSeed: RemoteOptionSeed[] = useMemo(
        () =>
            optionSeeds(
                (initialData?.lines ?? []).flatMap((line) =>
                    (line.lots ?? []).map((lot) => ({
                        id: lot.lot_id,
                        label: lot.lot_number,
                    })),
                ),
                data.lines.flatMap((line) =>
                    line.lots.map((lot) => lot.lot_id),
                ),
            ),
        [initialData, data.lines],
    );

    const serialSeed: RemoteOptionSeed[] = useMemo(
        () =>
            optionSeeds(
                (initialData?.lines ?? []).flatMap((line) =>
                    (line.serials ?? []).map((serial) => ({
                        id: serial.serial_id,
                        label: serial.serial_number,
                    })),
                ),
                data.lines.flatMap((line) =>
                    line.serials.map((serial) => serial.serial_id),
                ),
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

    /** Las direcciones del cliente elegido: de ahí sale la de entrega. */
    const addresses: ClientAddressOption[] =
        (
            (client.optionOf(data.client_id)?.meta ??
                {}) as Partial<ClientOptionMeta>
        ).addresses ?? [];

    /**
     * Cambiar de cliente invalida el pedido elegido, que era de otro, y con él
     * la trazabilidad de las líneas. La dirección se estrena con la de entrega
     * marcada por defecto.
     */
    const selectClient = (option: AjaxOption | null) => {
        client.select(option);
        source.select(null);

        const meta = (option?.meta ?? {}) as Partial<ClientOptionMeta>;
        const shipping = (meta.addresses ?? []).find(
            (address) =>
                address.type === 'shipping' && address.is_default === 'yes',
        );

        setData((current) => ({
            ...current,
            client_id: option?.value ?? '',
            client_address_id: shipping?.id ?? '',
            sourceable_type: '',
            sourceable_id: '',
            lines: current.lines.map((line) => ({
                ...line,
                sourceable_id: '',
            })),
        }));
    };

    /**
     * Elegir el pedido arma el despacho con lo que le queda por sacar: su
     * cabecera —cliente, bodega y dirección— y sus líneas pendientes, cada una
     * apuntando a la suya. Sustituye lo capturado: es el punto de partida del
     * despacho, no un añadido.
     *
     * Lo ya despachado por completo no vuelve, y la cantidad queda editable:
     * casi nunca sale el pedido entero de una vez.
     *
     * Las líneas se piden aparte y no viajan en el `meta` del select: el saldo
     * solo interesa del pedido elegido.
     */
    const selectSource = async (option: AjaxOption | null) => {
        source.select(option);

        if (!option) {
            pendingLines.clear();

            setData((current) => ({
                ...current,
                sourceable_type: '',
                sourceable_id: '',
                /** Sin pedido de origen, sus líneas tampoco pueden tenerlo. */
                lines: current.lines.map((line) => ({
                    ...line,
                    sourceable_id: '',
                })),
            }));

            return;
        }

        const meta = option.meta as unknown as SalesOrderOptionMeta;

        /** El cliente lo manda el pedido: despachar a otro sería otro documento. */
        client.select({
            value: meta.client_id,
            label: meta.client_name ?? meta.code,
        });

        setData((current) => ({
            ...current,
            sourceable_type: SALES_ORDER,
            sourceable_id: option.value,
            client_id: meta.client_id,
            client_address_id: meta.client_address_id ?? '',
            warehouse_id: meta.warehouse_id,
            lines: current.lines.map((line) => ({ ...line, location_id: '' })),
        }));

        const pending = await pendingLines.fetchLines(option.value);

        /** Un pedido sin saldo deja intacto lo que ya se había capturado. */
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
                sourceable_id: line.id,
                location_id: '',
                lots: [],
                serials: [],
                notes: line.notes ?? '',
            })),
        );
    };

    /** Las líneas del pedido elegido con su saldo, tal como las trajo el servidor. */
    const orderLines = pendingLines.lines;

    const orderLineOf = (id: string): PendingOrderLine | undefined =>
        id ? orderLines.find((line) => line.id === id) : undefined;

    /** Cuánto queda por despachar de una línea del pedido. */
    const remainingOf = (orderLineId: string): number =>
        round2(Number(orderLineOf(orderLineId)?.pending_quantity ?? 0));

    const addLine = () => setData('lines', [...data.lines, emptyLine()]);

    const removeLine = (index: number) =>
        setData(
            'lines',
            data.lines.filter((_, i) => i !== index),
        );

    const updateLine = <K extends keyof DispatchLineRow>(
        index: number,
        field: K,
        value: DispatchLineRow[K],
    ) =>
        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index ? { ...line, [field]: value } : line,
            ),
        );

    /**
     * Cambiar de artículo invalida la unidad, la trazabilidad y el vínculo con
     * la línea del pedido: ya no es la misma mercancía.
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
                          sourceable_id: '',
                          lots: [],
                          serials: [],
                      }
                    : line,
            ),
        );
    };

    /**
     * Atar una línea a la del pedido copia lo que se vendió: artículo y unidad.
     * Es lo que hace que el backend pueda comprobar que no se despacha más de
     * lo pedido —y de donde saca el precio con el que la guía se imprime—.
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
                    return { ...line, sourceable_id: '' };
                }

                return {
                    ...line,
                    sourceable_id: source.id,
                    item_id: source.item_id,
                    measurement_unit_id: source.measurement_unit_id,
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

    /**
     * Elegir la ruta estrena lo que la ruta ya sabe —de qué bodega sale, quién
     * conduce y en qué—, pero **solo lo que esté vacío**: si el usuario ya
     * eligió una bodega, sus líneas pueden tener ubicaciones de ella y
     * pisársela sería destructivo.
     *
     * Limpiarla no deshace nada de eso: el despacho se queda con lo que ya
     * tiene, simplemente deja de estar asignado a un recorrido.
     */
    const selectRoute = (option: AjaxOption | null) => {
        deliveryRoute.select(option);

        const meta = (option?.meta ?? {}) as Partial<RouteOptionMeta>;

        setData((current) => ({
            ...current,
            route_id: option?.value ?? '',
            warehouse_id: current.warehouse_id || (meta.warehouse_id ?? ''),
            driver_id: current.driver_id || (meta.driver_id ?? ''),
            vehicle_plate: current.vehicle_plate || (meta.vehicle_plate ?? ''),
        }));
    };

    /**
     * Cuántos bultos salen. La pantalla no enseña importes: el precio lo pone
     * el backend con el pedido o con el promedio del artículo, y en el despacho
     * es informativo de todas formas —la guía no factura—.
     */
    const totals: DispatchTotals = data.lines.reduce(
        (accumulator, line) => ({
            lines: accumulator.lines + 1,
            quantity: round2(accumulator.quantity + line.quantity),
        }),
        { lines: 0, quantity: 0 },
    );

    /** ---- Trazabilidad de la línea: lotes y series ---- */

    const mapLine = (
        index: number,
        change: (line: DispatchLineRow) => DispatchLineRow,
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
                    lot_id: '',
                    /** Lo que falta por repartir: casi siempre es todo. */
                    quantity: Math.max(
                        round2(line.quantity - assignedToLots(line)),
                        0,
                    ),
                    status: 'active' as const,
                },
            ],
        }));

    const setLineLot = (
        index: number,
        lotIndex: number,
        option: AjaxOption | null,
    ) => {
        if (option) {
            lots.remember(option);
        }

        mapLine(index, (line) => ({
            ...line,
            lots: line.lots.map((lot, i) =>
                i === lotIndex ? { ...lot, lot_id: option?.value ?? '' } : lot,
            ),
        }));
    };

    const updateLineLot = (index: number, lotIndex: number, quantity: number) =>
        mapLine(index, (line) => ({
            ...line,
            lots: line.lots.map((lot, i) =>
                i === lotIndex ? { ...lot, quantity } : lot,
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
                serial.lot_id === lot.lot_id
                    ? { ...serial, lot_id: '' }
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

    const addLineSerial = (index: number) =>
        mapLine(index, (line) => ({
            ...line,
            serials: [
                ...line.serials,
                {
                    id: generateUUID(),
                    serial_id: '',
                    lot_id: '',
                    status: 'active' as const,
                },
            ],
        }));

    const setLineSerial = (
        index: number,
        serialIndex: number,
        option: AjaxOption | null,
    ) => {
        if (option) {
            serials.remember(option);
        }

        mapLine(index, (line) => ({
            ...line,
            serials: line.serials.map((serial, i) =>
                i === serialIndex
                    ? { ...serial, serial_id: option?.value ?? '' }
                    : serial,
            ),
        }));
    };

    const setLineSerialLot = (
        index: number,
        serialIndex: number,
        lotId: string,
    ) =>
        mapLine(index, (line) => ({
            ...line,
            serials: line.serials.map((serial, i) =>
                i === serialIndex ? { ...serial, lot_id: lotId } : serial,
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

    /** Lo que pidió la línea del pedido. Vacío en una línea suelta. */
    const orderedQuantityOf = (line: DispatchLineRow): number | null => {
        const source = orderLineOf(line.sourceable_id);

        return source ? Number(source.quantity) : null;
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        /**
         * El alias de la línea origen no se captura: lo pone la pantalla al
         * enviar, y solo en las líneas que de verdad vienen del pedido. El
         * backend lo exige junto al id porque el par forma el morph.
         */
        transform((payload) => ({
            ...payload,
            lines: payload.lines.map((line) => ({
                ...line,
                sourceable_type: line.sourceable_id ? SALES_ORDER_LINE : '',
            })),
        }));

        if (mode === 'create') {
            post(dispatches.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                dispatches.update({
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
        currency,
        totals,
        addresses,
        clientLookupUrl: client.url,
        clientOption: client.optionOf(data.client_id),
        selectClient,
        sourceLookupUrl: source.url,
        sourceOption: source.optionOf(data.sourceable_id),
        selectSource,
        orderLines,
        remainingOf,
        loadingOrderLines: pendingLines.loading,
        orderLinesFailed: pendingLines.failed,
        selectWarehouse,
        routeLookupUrl: deliveryRoute.url,
        routeOption: deliveryRoute.optionOf(data.route_id),
        selectRoute,
        locations,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineOrderLine,
        addLineLot,
        setLineLot,
        updateLineLot,
        removeLineLot,
        addLineSerial,
        setLineSerial,
        setLineSerialLot,
        removeLineSerial,
        orderedQuantityOf,
        catalog,
        lots,
        serials,
        /** El alias que el backend espera en la línea atada al pedido. */
        sourceLineType: SALES_ORDER_LINE,
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
