import type { StatusKind } from '@/components/status-pill';

export type ItemLotStatus = 'active' | 'blocked' | 'expired';

export interface ItemLot {
    id: string;
    company_id: string | null;
    code: string;
    item_id: string;
    item_name?: string | null;
    item_code?: string | null;
    lot_number: string;
    manufactured_at: string | null;
    expires_at: string | null;
    supplier_id: string | null;
    supplier_name?: string | null;
    status: ItemLotStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface ItemLotMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface ItemLotFilters {
    code?: string;
    lot_number?: string;
    item_id?: string;
    supplier_id?: string;
    status?: string;
    expires_before?: string;
    limit?: number;
    offset?: number;
}

export const LOT_STATUS_LABELS: Record<ItemLotStatus, string> = {
    active: 'Activo',
    blocked: 'Retenido',
    expired: 'Vencido',
};

/** El lote no usa los estados activo/inactivo, así que la pastilla se mapea. */
export const LOT_STATUS_PILL: Record<ItemLotStatus, StatusKind> = {
    active: 'activo',
    blocked: 'moroso',
    expired: 'vencido',
};
