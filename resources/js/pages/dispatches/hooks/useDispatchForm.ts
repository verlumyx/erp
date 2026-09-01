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
import { generateUUID } from '@/lib/utils';
import clients from '@/routes/clients';
import dispatches from '@/routes/dispatches';
import itemLots from '@/routes/item-lots';
import itemSerials from '@/routes/item-serials';
import routes from '@/routes/routes';
import salesOrders from '@/routes/sales-orders';
import { taxWithholdingPercent, type TaxOption } from '@/types/tax';
import type {
    ClientAddressOption,
    ClientOptionMeta,
    Dispatch,
    DispatchOptions,
    RouteOptionMeta,
    SalesOrderOptionLine,
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

export interface DispatchLineRow {
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
    /** Línea del pedido que esta línea despacha; vacía en una suelta. */
    sourceable_id: string;
    lot_id: string;
    serial_id: string;
    /** Vacía deja que el kardex tome la ubicación por defecto de la bodega. */
    location_id: string;
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
    if (!model?.client_id) {
        return null;
    }

    const name = model.client_name ?? '';

    return {
        value: model.client_id,
        label: model.client_code ? `${model.client_code} — ${name}` : name,
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
function withTax(
    line: DispatchLineRow,
    tax: TaxOption | undefined,
): DispatchLineRow {
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
    /** Cantidad por precio, antes de cualquier rebaja. */
    gross: number;
    discountAmount: number;
    subtotal: number;
    taxAmount: number;
    /** Parte del impuesto que se entera al fisco en vez de cobrarse. */
    withholdingAmount: number;
    total: number;
    /** Bultos y costo de la carga: lo que el despacho mira de verdad. */
    quantity: number;
}

function emptyLine(): DispatchLineRow {
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
        sourceable_id: '',
        lot_id: '',
        serial_id: '',
        location_id: '',
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
            unit_price: Number(line.unit_price),
            discount_percent: Number(line.discount_percent),
            tax_id: line.tax_id ?? '',
            tax_percent: Number(line.tax_percent),
            withholding_percent: Number(line.withholding_percent),
            sourceable_id: line.sourceable_id ?? '',
            lot_id: line.lot_id ?? '',
            serial_id: line.serial_id ?? '',
            location_id: line.location_id ?? '',
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine()];
}

/**
 * Espejo del cálculo del backend (`DispatchLineData`): sirve para mostrar el
 * resumen mientras se captura. El importe que se guarda siempre lo recalcula el
 * servidor, y en el despacho es informativo: la guía no factura.
 */
export function lineAmounts(line: DispatchLineRow): {
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

    /**
     * Y los pedidos contra el suyo, acotados al cliente elegido. Se hidrata
     * porque de su `meta` salen las líneas que el despacho copia, y eso tiene
     * que estar también al abrir un despacho ya guardado.
     */
    const source = useRemoteOption({
        url: salesOrders.lookup(companyId).url,
        seed: sourceSeed(initialData),
        hydrate: true,
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
            client_id: initialData?.client_id ?? '',
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

    /** Lotes y series elegidos en las líneas. */
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
     * Elegir el pedido copia su cabecera —cliente, bodega y dirección—, pero no
     * sus líneas: casi nunca se despacha el pedido entero de una vez. Para
     * traerlas está `copyOrderLines`.
     */
    const selectSource = (option: AjaxOption | null) => {
        source.select(option);

        if (!option) {
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
    };

    /** Las líneas del pedido elegido, tal como llegan en el `meta`. */
    const orderLines: SalesOrderOptionLine[] =
        (
            (source.optionOf(data.sourceable_id)?.meta ??
                {}) as Partial<SalesOrderOptionMeta>
        ).lines ?? [];

    const orderLineOf = (id: string): SalesOrderOptionLine | undefined =>
        id ? orderLines.find((line) => line.id === id) : undefined;

    /** Cuánto queda por despachar de una línea del pedido. */
    const remainingOf = (orderLineId: string): number => {
        const line = orderLineOf(orderLineId);

        if (!line) {
            return 0;
        }

        return round2(Number(line.quantity) - Number(line.dispatched_quantity));
    };

    /**
     * Trae las líneas del pedido al formulario, cada una ya apuntando a la suya
     * y con lo que aún queda por sacar. Sustituye lo capturado: es el punto de
     * partida del despacho, no un añadido.
     */
    const copyOrderLines = () => {
        const pending = orderLines.filter(
            (line) =>
                Number(line.quantity) - Number(line.dispatched_quantity) > 0,
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
                    Number(line.quantity) - Number(line.dispatched_quantity),
                ),
                unit_price: Number(line.unit_price),
                discount_percent: Number(line.discount_percent),
                tax_id: line.tax_id ?? '',
                tax_percent: Number(line.tax_percent),
                withholding_percent: Number(line.withholding_percent),
                sourceable_id: line.id,
                lot_id: '',
                serial_id: '',
                location_id: '',
                notes: line.notes ?? '',
            })),
        );
    };

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

    /** El impuesto del catálogo con ese id, si sigue activo. */
    const taxOf = (taxId: string | null | undefined): TaxOption | undefined =>
        taxId ? options.taxes.find((tax) => tax.id === taxId) : undefined;

    /**
     * Cambiar de artículo invalida la unidad, el lote, la serie y la
     * trazabilidad a la línea del pedido: ya no es la misma mercancía.
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
                              sourceable_id: '',
                              lot_id: '',
                              serial_id: '',
                          },
                          taxOf(item?.sale_tax_id),
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
     * Atar una línea a la del pedido copia lo que se vendió: artículo, unidad,
     * precio y sus cargos. Es lo que hace que el backend pueda comprobar que no
     * se despacha más de lo pedido.
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
                    unit_price: Number(source.unit_price),
                    discount_percent: Number(source.discount_percent),
                    tax_id: source.tax_id ?? '',
                    tax_percent: Number(source.tax_percent),
                    withholding_percent: Number(source.withholding_percent),
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

    const totals: DispatchTotals = data.lines.reduce(
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
                quantity: round2(accumulator.quantity + line.quantity),
            };
        },
        {
            gross: 0,
            discountAmount: 0,
            subtotal: 0,
            taxAmount: 0,
            withholdingAmount: 0,
            total: 0,
            quantity: 0,
        },
    );

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
        copyOrderLines,
        selectWarehouse,
        routeLookupUrl: deliveryRoute.url,
        routeOption: deliveryRoute.optionOf(data.route_id),
        selectRoute,
        locations,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineTax,
        setLineLot,
        setLineSerial,
        setLineOrderLine,
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
