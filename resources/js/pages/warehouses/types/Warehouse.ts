export type WarehouseType =
    | 'main'
    | 'branch'
    | 'transit'
    | 'quarantine'
    | 'virtual';

export type YesNo = 'yes' | 'no';

export interface Warehouse {
    id: string;
    company_id: string | null;
    code: string;
    name: string;
    type: WarehouseType;
    address: string | null;
    phone: string | null;
    city: string | null;
    responsible_user_id: string | null;
    responsible_user_name?: string | null;
    is_default: YesNo;
    allows_negative_stock: YesNo;
    uses_locations: YesNo;
    is_sales_available: YesNo;
    notes: string | null;
    status: 'active' | 'inactive';
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface WarehouseUserOption {
    id: string;
    name: string;
}

export interface WarehouseMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface WarehouseFilters {
    name?: string;
    code?: string;
    city?: string;
    type?: string;
    status?: string;
    limit?: number;
    offset?: number;
}

export const WAREHOUSE_TYPE_LABELS: Record<WarehouseType, string> = {
    main: 'Principal',
    branch: 'Sucursal',
    transit: 'Tránsito',
    quarantine: 'Cuarentena',
    virtual: 'Virtual',
};
