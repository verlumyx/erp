import type { StatusKind } from '@/components/status-pill';

export type ItemSerialStatus =
    | 'available'
    | 'reserved'
    | 'sold'
    | 'returned'
    | 'scrapped';

export interface ItemSerial {
    id: string;
    company_id: string | null;
    code: string;
    item_id: string;
    item_name?: string | null;
    item_code?: string | null;
    serial_number: string;
    lot_id: string | null;
    lot_number?: string | null;
    warehouse_id: string | null;
    warehouse_name?: string | null;
    status: ItemSerialStatus;
    sold_at: string | null;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface WarehouseOption {
    id: string;
    name: string;
}

export interface ItemSerialMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface ItemSerialFilters {
    code?: string;
    serial_number?: string;
    item_id?: string;
    lot_id?: string;
    warehouse_id?: string;
    status?: string;
    limit?: number;
    offset?: number;
}

export const SERIAL_STATUS_LABELS: Record<ItemSerialStatus, string> = {
    available: 'Disponible',
    reserved: 'Reservada',
    sold: 'Vendida',
    returned: 'Devuelta',
    scrapped: 'De baja',
};

/** La serie tiene su propio ciclo de vida, así que la pastilla se mapea. */
export const SERIAL_STATUS_PILL: Record<ItemSerialStatus, StatusKind> = {
    available: 'activo',
    reserved: 'pendiente',
    sold: 'pagado',
    returned: 'libre',
    scrapped: 'inactivo',
};
