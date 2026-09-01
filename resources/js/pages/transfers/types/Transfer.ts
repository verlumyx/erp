import type { StatusKind } from '@/components/status-pill';
import { formatMoney } from '@/lib/money';

export type TransferStatus =
    | 'draft'
    | 'confirmed'
    | 'partial'
    | 'completed'
    | 'cancelled';

/**
 * Dónde está la mercancía. Es un eje distinto del estado del documento: el
 * traslado se confirma y se cumple, la mercancía sale, viaja y llega.
 */
export type TransferMovementStatus =
    | 'pending'
    | 'in_transit'
    | 'received'
    | 'partial_received';

export type TransferReason =
    | 'restock'
    | 'rebalance'
    | 'damaged'
    | 'quarantine'
    | 'other';

export interface TransferLine {
    id: string;
    transfer_id: string;
    line_number: number;
    item_id: string;
    item_name?: string;
    item_code?: string;
    measurement_unit_id: string;
    measurement_unit_name?: string;
    origin_location_id: string | null;
    origin_location_name?: string | null;
    destination_location_id: string | null;
    destination_location_name?: string | null;
    lot_id: string | null;
    lot_number?: string | null;
    serial_id: string | null;
    serial_number?: string | null;
    quantity: string;
    base_quantity: string;
    /** El traslado no pone precio: estos importes son el costo que viaja. */
    unit_price: string;
    subtotal: string;
    total: string;
    sent_quantity: string;
    received_quantity: string;
    difference_quantity: string;
    /** Costo con el que viaja: en borrador el promedio, confirmado el real. */
    unit_cost: string;
    status: 'active' | 'inactive';
    notes: string | null;
}

export interface Transfer {
    id: string;
    company_id: string | null;
    code: string;
    origin_warehouse_id: string;
    origin_warehouse_name?: string;
    destination_warehouse_id: string;
    destination_warehouse_name?: string;
    /** Con ella el traslado va en dos pasos; sin ella, en uno. */
    transit_warehouse_id: string | null;
    transit_warehouse_name?: string | null;
    transfer_date: string;
    expected_date: string | null;
    received_date: string | null;
    reason: TransferReason;
    reason_detail: string | null;
    driver_id: string | null;
    driver_name?: string | null;
    vehicle_plate: string | null;
    route_id: string | null;
    total_quantity: string;
    total_cost: string;
    transfer_status: TransferMovementStatus;
    sent_by: string | null;
    sent_by_name?: string | null;
    received_by: string | null;
    received_by_name?: string | null;
    cancelled_at: string | null;
    notes: string | null;
    status: TransferStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    lines?: TransferLine[];
}

export interface TransferMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface TransferFilters {
    code?: string;
    origin_warehouse_id?: string;
    destination_warehouse_id?: string;
    warehouse_id?: string;
    driver_id?: string;
    route_id?: string;
    reason?: string;
    transfer_status?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
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
 * Ni los artículos, ni los lotes, ni las series están aquí: son padrones
 * demasiado grandes para las props y la pantalla los busca contra sus endpoints
 * de lookup con `Select2Ajax`. Tampoco hay impuestos: el traslado no grava nada.
 */
export interface TransferOptions {
    warehouses: Array<{ id: string; name: string; type?: string }>;
    locations: WarehouseLocationOption[];
    drivers: Array<{ id: string; name: string }>;
}

export const STATUS_LABELS: Record<TransferStatus, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmado',
    partial: 'Recibido a medias',
    completed: 'Cumplido',
    cancelled: 'Anulado',
};

export const MOVEMENT_STATUS_LABELS: Record<TransferMovementStatus, string> = {
    pending: 'En bodega',
    in_transit: 'En camino',
    received: 'Recibido',
    partial_received: 'Recibido a medias',
};

export const REASON_LABELS: Record<TransferReason, string> = {
    restock: 'Reabastecimiento',
    rebalance: 'Reequilibrio',
    damaged: 'Mercancía dañada',
    quarantine: 'Cuarentena',
    other: 'Otro',
};

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<TransferStatus, StatusKind> = {
    draft: 'inactivo',
    confirmed: 'libre',
    partial: 'libre',
    completed: 'pagado',
    cancelled: 'vencido',
};

export const MOVEMENT_PILL_KIND: Record<TransferMovementStatus, StatusKind> = {
    pending: 'inactivo',
    in_transit: 'libre',
    received: 'pagado',
    partial_received: 'libre',
};

/**
 * Transiciones permitidas. Espejo de `Transfer::STATUS_TRANSITIONS`: la
 * pantalla solo ofrece lo que el backend acepta. `partial` no aparece como
 * destino porque no se elige: lo escribe la recepción.
 */
export const STATUS_TRANSITIONS: Record<TransferStatus, TransferStatus[]> = {
    draft: ['confirmed', 'cancelled'],
    confirmed: ['completed', 'cancelled'],
    partial: ['completed', 'cancelled'],
    completed: [],
    cancelled: [],
};

/** Resultados con los que se cierra un viaje. */
export const SETTLED_MOVEMENT_STATUSES: TransferMovementStatus[] = [
    'received',
    'partial_received',
];

export function isEditable(status: TransferStatus): boolean {
    return status === 'draft';
}

/** La mercancía ya llegó al destino, entera o a medias. */
export function isReceiptSettled(status: TransferMovementStatus): boolean {
    return SETTLED_MOVEMENT_STATUSES.includes(status);
}

/** El traslado viaja en dos pasos: lo decide la bodega de tránsito. */
export function isTwoStep(model: Transfer): boolean {
    return model.transit_warehouse_id !== null;
}

/**
 * Se registra la llegada de lo que está en la calle, y una sola vez. Un
 * traslado inmediato no tiene recepción: llegó al confirmarse.
 */
export function canRegisterReceipt(model: Transfer): boolean {
    return (
        isTwoStep(model) &&
        model.status === 'confirmed' &&
        !isReceiptSettled(model.transfer_status)
    );
}

export function formatAmount(value: number | string, currency: string): string {
    return formatMoney(value, currency);
}
