export type WarehouseLocationType = 'zone' | 'aisle' | 'shelf' | 'bin';

export type YesNo = 'yes' | 'no';

export interface WarehouseLocation {
    id: string;
    company_id: string | null;
    code: string;
    warehouse_id: string;
    warehouse_name?: string | null;
    parent_id: string | null;
    parent_name?: string | null;
    name: string;
    location_code: string;
    type: WarehouseLocationType;
    capacity: number;
    is_default: YesNo;
    status: 'active' | 'inactive';
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface WarehouseOption {
    id: string;
    name: string;
}

export interface ParentOption {
    id: string;
    warehouse_id: string;
    name: string;
    location_code: string;
}

export interface WarehouseLocationMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface WarehouseLocationFilters {
    name?: string;
    code?: string;
    location_code?: string;
    warehouse_id?: string;
    type?: string;
    status?: string;
    limit?: number;
    offset?: number;
}

export const LOCATION_TYPE_LABELS: Record<WarehouseLocationType, string> = {
    zone: 'Zona',
    aisle: 'Pasillo',
    shelf: 'Estante',
    bin: 'Posición',
};
