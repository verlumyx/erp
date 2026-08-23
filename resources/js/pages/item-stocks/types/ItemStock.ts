export interface ItemStock {
    id: string;
    company_id: string | null;
    item_id: string;
    item_name?: string | null;
    item_code?: string | null;
    item_sku?: string | null;
    warehouse_id: string;
    warehouse_name?: string | null;
    location_id: string;
    location_name?: string | null;
    location_code?: string | null;
    lot_id: string | null;
    lot_number?: string | null;
    quantity: number;
    reserved_quantity: number;
    incoming_quantity: number;
    available_quantity: number;
    average_cost: number;
    total_value: number;
    last_movement_at: string | null;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string | null;
}

export interface WarehouseOption {
    id: string;
    name: string;
}

export interface ItemStockMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface ItemStockFilters {
    q?: string;
    item_id?: string;
    warehouse_id?: string;
    location_id?: string;
    lot_id?: string;
    status?: string;
    with_stock?: string;
    limit?: number;
    offset?: number;
}

/** El saldo se muestra en la unidad base, con cuatro decimales como la columna. */
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
