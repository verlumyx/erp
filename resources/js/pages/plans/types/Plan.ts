export type PlanCapacity = 'profile' | 'full_account';

export interface PlanServiceRef {
    id: string;
    name: string;
    code: string;
}

export interface Plan {
    id: string;
    company_id: string | null;
    code: string;
    service_id: string;
    name: string;
    capacity: PlanCapacity;
    duration_days: number;
    sale_price: string;
    roi_target_pct: string;
    active: boolean;
    service?: PlanServiceRef | null;
    created_at: string;
    updated_at: string | null;
}

export interface PlanServiceOption {
    id: string;
    name: string;
    code: string;
    max_profiles: number;
}

export interface PlanMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface PlanFilters {
    name?: string;
    code?: string;
    capacity?: string;
    service_id?: string;
    active?: string;
    limit?: number;
    offset?: number;
}

export const CAPACITY_LABELS: Record<PlanCapacity, string> = {
    profile: 'Perfil',
    full_account: 'Cuenta completa',
};
