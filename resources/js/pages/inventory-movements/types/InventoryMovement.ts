export type MovementType =
    | 'in'
    | 'out'
    | 'transfer_in'
    | 'transfer_out'
    | 'adjustment_in'
    | 'adjustment_out';

export type MovementDirection = 'in' | 'out';

export type MovementStatus = 'active' | 'reversed';

export interface InventoryMovement {
    id: string;
    company_id: string | null;
    code: string;
    movement_date: string | null;
    type: MovementType;
    direction: MovementDirection;
    origin_type: string;
    origin_id: string;
    origin_line_id: string | null;
    item_id: string;
    item_name?: string | null;
    item_code?: string | null;
    item_sku?: string | null;
    warehouse_id: string;
    warehouse_name?: string | null;
    location_id: string | null;
    location_name?: string | null;
    location_code?: string | null;
    lot_id: string | null;
    lot_number?: string | null;
    serial_id: string | null;
    serial_number?: string | null;
    quantity: number;
    unit_cost: number;
    total_cost: number;
    balance_quantity: number;
    balance_cost: number;
    balance_value: number;
    reversal_of_id: string | null;
    reversal_of_code?: string | null;
    reversal_id?: string | null;
    reversal_code?: string | null;
    status: MovementStatus;
    notes: string | null;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface WarehouseOption {
    id: string;
    name: string;
}

export interface InventoryMovementMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface InventoryMovementFilters {
    q?: string;
    item_id?: string;
    warehouse_id?: string;
    lot_id?: string;
    type?: string;
    direction?: string;
    origin_type?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

export const MOVEMENT_TYPE_LABELS: Record<MovementType, string> = {
    in: 'Entrada',
    out: 'Salida',
    transfer_in: 'Traslado recibido',
    transfer_out: 'Traslado enviado',
    adjustment_in: 'Ajuste positivo',
    adjustment_out: 'Ajuste negativo',
};

/** Documento que originó el movimiento; la lista crece con Logística. */
export const ORIGIN_TYPE_LABELS: Record<string, string> = {
    purchase_invoice: 'Factura de compra',
    sales_invoice: 'Factura de venta',
    dispatch: 'Despacho',
    transfer: 'Traslado',
    entry: 'Entrada',
    adjustment: 'Ajuste',
    purchase_return: 'Devolución de compra',
    sales_return: 'Devolución de venta',
};

export const originLabel = (value: string): string =>
    ORIGIN_TYPE_LABELS[value] ?? value;

/** Las cantidades van en la unidad base, con cuatro decimales como la columna. */
export const formatQuantity = (value: number): string =>
    new Intl.NumberFormat('es-VE', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 4,
    }).format(value);

export const formatAmount = (value: number): string =>
    new Intl.NumberFormat('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);

/** El costo unitario se muestra con los seis decimales que guarda la columna. */
export const formatCost = (value: number): string =>
    new Intl.NumberFormat('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 6,
    }).format(value);

/** La cantidad se guarda siempre positiva: el signo lo pone la dirección. */
export const signedQuantity = (movement: InventoryMovement): string =>
    `${movement.direction === 'in' ? '+' : '−'}${formatQuantity(movement.quantity)}`;
