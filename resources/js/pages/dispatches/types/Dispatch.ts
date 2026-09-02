import type { StatusKind } from '@/components/status-pill';
import { formatMoney } from '@/lib/money';
import type { TaxOption } from '@/types/tax';

export type DispatchStatus = 'draft' | 'confirmed' | 'completed' | 'cancelled';

/**
 * Cómo terminó el viaje. Es un eje distinto del estado del documento: el
 * despacho se confirma y se cumple, la mercancía se entrega o se rechaza.
 */
export type DeliveryStatus =
    | 'pending'
    | 'in_transit'
    | 'delivered'
    | 'partial_delivered'
    | 'rejected'
    | 'returned';

/** Alias del morph map admitidos como documento origen. */
export type DispatchSourceType = 'sales_order';

/** Uno de los lotes de los que sale una línea; siempre del maestro. */
export interface DispatchLineLot {
    id: string;
    dispatch_line_id: string;
    line_number: number;
    lot_id: string;
    lot_number?: string | null;
    quantity: string;
    base_quantity: string;
    status: 'active' | 'inactive';
    notes: string | null;
}

/** Una de las unidades con serie que salen en una línea. */
export interface DispatchLineSerial {
    id: string;
    dispatch_line_id: string;
    dispatch_line_lot_id: string | null;
    line_number: number;
    serial_id: string;
    serial_number?: string | null;
    status: 'active' | 'inactive';
}

export interface DispatchLine {
    id: string;
    dispatch_id: string;
    line_number: number;
    item_id: string;
    item_name?: string;
    item_code?: string;
    measurement_unit_id: string;
    measurement_unit_name?: string;
    /** Línea del pedido que esta línea despacha. */
    sourceable_type: string | null;
    sourceable_id: string | null;
    /** La trazabilidad vive en sus propias tablas: la línea solo la agrupa. */
    lots: DispatchLineLot[];
    serials: DispatchLineSerial[];
    /** Lo que pidió la línea del pedido. Vacío en una línea sin origen. */
    source_quantity?: string | null;
    location_id: string | null;
    location_name?: string | null;
    quantity: string;
    base_quantity: string;
    unit_price: string;
    discount_percent: string;
    discount_amount: string;
    tax_id: string | null;
    tax_percent: string;
    tax_amount: string;
    withholding_percent: string;
    withholding_amount: string;
    subtotal: string;
    total: string;
    /** Lo que el cliente recibió y lo que devolvió en el mismo viaje. */
    delivered_quantity: string;
    returned_quantity: string;
    /** Costo con el que salió: en borrador el promedio, confirmado el real. */
    unit_cost: string;
    status: 'active' | 'inactive';
    notes: string | null;
}

export interface Dispatch {
    id: string;
    company_id: string | null;
    code: string;
    /**
     * A quién va la mercancía: un cliente en un despacho de venta, una bodega
     * propia en uno que sirve un traslado.
     */
    recipient_type: 'client' | 'warehouse' | null;
    recipient_id: string | null;
    recipient_name?: string;
    recipient_code?: string;
    /** Documento origen. Vacío en un despacho directo, sin pedido previo. */
    sourceable_type: string | null;
    sourceable_id: string | null;
    sourceable_code?: string | null;
    client_address_id: string | null;
    client_address_name?: string | null;
    warehouse_id: string;
    warehouse_name?: string;
    route_id: string | null;
    route_code?: string | null;
    route_name?: string | null;
    /** La parada la escribe la planificación de la ruta, no esta pantalla. */
    route_stop_id: string | null;
    dispatch_date: string;
    delivery_date: string | null;
    driver_id: string | null;
    driver_name?: string | null;
    vehicle_plate: string | null;
    carrier: string | null;
    tracking_number: string | null;
    freight_amount: string;
    total_quantity: string;
    total_weight: string;
    total_volume: string;
    total_cost: string;
    delivery_status: DeliveryStatus;
    received_by_name: string | null;
    received_by_document: string | null;
    signature_path: string | null;
    evidence_path: string | null;
    latitude: string | null;
    longitude: string | null;
    rejection_reason: string | null;
    cancelled_at: string | null;
    notes: string | null;
    status: DispatchStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    lines?: DispatchLine[];
}

export interface DispatchMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface DispatchFilters {
    code?: string;
    recipient_id?: string;
    warehouse_id?: string;
    driver_id?: string;
    route_id?: string;
    tracking_number?: string;
    delivery_status?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

/**
 * Lo que la ruta trae en el `meta` de su opción: con ello el despacho estrena
 * bodega, conductor y placa sin una segunda ida al servidor.
 */
export interface RouteOptionMeta {
    code: string | null;
    name: string;
    type: string;
    zone: string | null;
    warehouse_id: string | null;
    warehouse_name: string | null;
    driver_id: string | null;
    driver_name: string | null;
    vehicle_plate: string | null;
    status: 'active' | 'inactive';
}

/** Una dirección del cliente, tal como llega en el `meta` de su opción. */
export interface ClientAddressOption {
    id: string;
    type: string;
    name: string;
    address: string;
    is_default: 'yes' | 'no';
}

/**
 * Lo que el despacho sabe de un cliente con solo haberlo elegido: viaja en el
 * `meta` de la opción que devuelve `clients.lookup`.
 */
export interface ClientOptionMeta {
    code: string;
    name: string;
    status: 'active' | 'inactive';
    addresses?: ClientAddressOption[];
}

/** Una línea del pedido de origen, tal como llega en el `meta` de su opción. */
export interface SalesOrderOptionLine {
    id: string;
    item_id: string;
    item_name: string | null;
    item_sku: string | null;
    measurement_unit_id: string;
    measurement_unit_name: string | null;
    quantity: string;
    /** Lo ya despachado: de ahí sale cuánto queda por sacar. */
    dispatched_quantity: string;
    reserved_quantity: string;
    invoiced_quantity: string;
    unit_price: string;
    discount_percent: string;
    tax_id: string | null;
    tax_percent: string;
    withholding_percent: string;
    notes: string | null;
}

/**
 * Lo que el despacho copia del pedido que lo origina: viaja en el `meta` de la
 * opción que devuelve `sales-orders.lookup`.
 */
export interface SalesOrderOptionMeta {
    code: string;
    client_id: string;
    client_name?: string;
    client_address_id: string | null;
    warehouse_id: string;
    salesperson_id: string | null;
    currency: string;
    status: string;
    lines?: SalesOrderOptionLine[];
}

/** Una ubicación de bodega, tal como llega en las props del formulario. */
export interface WarehouseLocationOption {
    id: string;
    warehouse_id: string;
    name: string;
    is_default: 'yes' | 'no';
}

/**
 * Catálogos que alimentan los selects del formulario.
 *
 * Ni los clientes, ni los artículos, ni los pedidos, ni los lotes, ni las
 * series están aquí: son padrones demasiado grandes para las props y la
 * pantalla los busca contra sus endpoints de lookup con `Select2Ajax`.
 */
export interface DispatchOptions {
    warehouses: Array<{ id: string; name: string; type?: string }>;
    locations: WarehouseLocationOption[];
    taxes: TaxOption[];
    drivers: Array<{ id: string; name: string }>;
}

export const STATUS_LABELS: Record<DispatchStatus, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmado',
    completed: 'Cumplido',
    cancelled: 'Anulado',
};

export const DELIVERY_STATUS_LABELS: Record<DeliveryStatus, string> = {
    pending: 'En bodega',
    in_transit: 'En camino',
    delivered: 'Entregado',
    partial_delivered: 'Entregado a medias',
    rejected: 'Rechazado',
    returned: 'Devuelto',
};

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<DispatchStatus, StatusKind> = {
    draft: 'inactivo',
    confirmed: 'libre',
    completed: 'pagado',
    cancelled: 'vencido',
};

export const DELIVERY_PILL_KIND: Record<DeliveryStatus, StatusKind> = {
    pending: 'inactivo',
    in_transit: 'libre',
    delivered: 'pagado',
    partial_delivered: 'libre',
    rejected: 'vencido',
    returned: 'vencido',
};

/**
 * Transiciones permitidas. Espejo de `Dispatch::STATUS_TRANSITIONS`: la
 * pantalla solo ofrece lo que el backend acepta.
 */
export const STATUS_TRANSITIONS: Record<DispatchStatus, DispatchStatus[]> = {
    draft: ['confirmed', 'cancelled'],
    confirmed: ['completed', 'cancelled'],
    completed: [],
    cancelled: [],
};

/** Resultados con los que se cierra un viaje. */
export const SETTLED_DELIVERY_STATUSES: DeliveryStatus[] = [
    'delivered',
    'partial_delivered',
    'rejected',
    'returned',
];

export function isEditable(status: DispatchStatus): boolean {
    return status === 'draft';
}

/** La entrega ya está registrada: el viaje terminó de una forma o de otra. */
export function isDeliverySettled(status: DeliveryStatus): boolean {
    return SETTLED_DELIVERY_STATUSES.includes(status);
}

/** Se registra la entrega de lo que está en la calle, y una sola vez. */
export function canRegisterDelivery(model: Dispatch): boolean {
    return (
        model.status === 'confirmed' &&
        !isDeliverySettled(model.delivery_status)
    );
}

export function formatAmount(value: number | string, currency: string): string {
    return formatMoney(value, currency);
}
