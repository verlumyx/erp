import type { StatusKind } from '@/components/status-pill';

export type LoadPlanStatus = 'draft' | 'in_transit' | 'confirmed' | 'cancelled';

export interface LoadPlanDispatchItem {
    id?: string;
    dispatch_id: string;
    sequence: number;
    status?: string;
    dispatch_code?: string;
    dispatch_date?: string;
    recipient_name?: string;
    recipient_code?: string | null;
    address?: string;
    order_code?: string | null;
    invoice_code?: string | null;
    total_quantity?: number;
    total_weight?: number;
    total_volume?: number;
    dispatch_status?: string;
}

export interface LoadPlan {
    id: string;
    company_id: string | null;
    code: string;
    plan_date: string;
    route_id: string;
    route_code?: string | null;
    route_name?: string | null;
    warehouse_id: string;
    warehouse_name?: string | null;
    vehicle_id: string;
    vehicle_code?: string | null;
    vehicle_plate?: string | null;
    vehicle_name?: string | null;
    vehicle_capacity_weight: number;
    vehicle_capacity_volume: number;
    driver_name: string;
    driver_id?: string | null;
    driver_user_name?: string | null;
    helper_name?: string | null;
    description?: string | null;
    total_dispatches: number;
    total_weight: number;
    total_volume: number;
    status: LoadPlanStatus;
    status_label?: string;
    is_editable: boolean;
    creator_name?: string | null;
    created_at: string;
    updated_at?: string | null;
    dispatches?: LoadPlanDispatchItem[];
}

export interface LoadPlanFormData {
    id?: string;
    plan_date: string;
    route_id: string;
    warehouse_id: string;
    vehicle_id: string;
    driver_name: string;
    driver_id: string;
    helper_name: string;
    description: string;
    dispatches: LoadPlanDispatchItem[];
}

export interface LoadPlanOptions {
    routes: Array<{
        id: string;
        code: string | null;
        name: string;
        warehouse_id: string | null;
        driver_id: string | null;
        driver_name: string | null;
        vehicle_plate: string | null;
    }>;
    warehouses: Array<{
        id: string;
        name: string;
    }>;
    vehicles: Array<{
        id: string;
        code: string | null;
        plate: string;
        name: string;
        capacity_weight: number;
        capacity_volume: number;
    }>;
    drivers: Array<{
        id: string;
        name: string;
    }>;
    statuses: Record<string, string>;
}

export interface LoadPlanMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface LoadPlanFilters {
    q?: string;
    code?: string;
    route_id?: string;
    warehouse_id?: string;
    vehicle_id?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

export const STATUS_LABELS: Record<LoadPlanStatus, string> = {
    draft: 'En proceso',
    in_transit: 'En tránsito',
    confirmed: 'Confirmado',
    cancelled: 'Anulado',
};

export const STATUS_PILL_KIND: Record<LoadPlanStatus, StatusKind> = {
    draft: 'inactivo',
    in_transit: 'libre',
    confirmed: 'pagado',
    cancelled: 'vencido',
};
