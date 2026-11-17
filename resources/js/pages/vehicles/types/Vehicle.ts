import type { StatusKind } from '@/components/status-pill';

export type VehicleStatus = 'active' | 'maintenance' | 'inactive';

export type VehicleType =
    | 'pickup'
    | 'cava_350'
    | 'truck'
    | 'heavy_truck'
    | 'other';

export interface Vehicle {
    id: string;
    company_id: string | null;
    code: string;
    plate: string;
    name: string;
    brand: string | null;
    model: string | null;
    year: number | null;
    vehicle_type: VehicleType;
    vehicle_type_label?: string;
    capacity_weight: number;
    capacity_volume: number;
    notes: string | null;
    status: VehicleStatus;
    creator_name?: string | null;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface VehicleFormData {
    id?: string;
    plate: string;
    name: string;
    brand: string;
    model: string;
    year: string | number;
    vehicle_type: VehicleType;
    capacity_weight: number;
    capacity_volume: number;
    notes: string;
}

export interface VehicleMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface VehicleFilters {
    q?: string;
    plate?: string;
    name?: string;
    brand?: string;
    model?: string;
    vehicle_type?: string;
    status?: string;
    limit?: number;
    offset?: number;
}

export const VEHICLE_TYPE_LABELS: Record<VehicleType, string> = {
    pickup: 'Camioneta / Pick-up',
    cava_350: 'Camión 350 / Cava Liviana',
    truck: 'Camión Mediano / NPR / Cargo',
    heavy_truck: 'Camión Pesado / Gandola / Chuto',
    other: 'Otro vehículo',
};

export const STATUS_LABELS: Record<VehicleStatus, string> = {
    active: 'Operativo',
    maintenance: 'En mantenimiento',
    inactive: 'Inactivo',
};

export const STATUS_PILL_KIND: Record<VehicleStatus, StatusKind> = {
    active: 'success',
    maintenance: 'warning',
    inactive: 'neutral',
};
