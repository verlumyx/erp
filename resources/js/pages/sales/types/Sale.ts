import type { StatusKind } from '@/components/status-pill';

export type SaleStatus = 'active' | 'expired' | 'cancelled';
export type SaleCapacity = 'profile' | 'full_account';

export interface SaleRef {
    id: string;
    name: string;
    code?: string | null;
}

export interface SalePlanRef extends SaleRef {
    duration_days?: number;
    sale_price?: string;
}

export interface AgentRef {
    id: string;
    name: string;
}

export interface SaleProfileRow {
    id: string;
    profile_id: string;
    number: number | null;
    profile_status: string | null;
    account: {
        id: string;
        code: string | null;
        email: string;
    } | null;
    created_at: string | null;
}

export interface SaleRenewalRow {
    id: string;
    sale_id: string;
    renewed_at: string | null;
    previous_end_date: string | null;
    new_end_date: string | null;
    duration_days: number;
    price: string;
    renewed_by: string | null;
    notes: string | null;
    created_at: string | null;
}

export interface SaleTransactionRow {
    id: string;
    type: string;
    category: string;
    amount: string;
    date: string | null;
    description: string;
}

export interface Sale {
    id: string;
    company_id: string;
    code: string;
    client_id: string;
    client?: SaleRef | null;
    plan_id: string;
    plan?: SalePlanRef | null;
    service_id: string;
    service?: SaleRef | null;
    agent_id: string;
    agent?: AgentRef | null;
    capacity: SaleCapacity;
    duration_days: number;
    price: string;
    start_date: string | null;
    end_date: string | null;
    status: SaleStatus;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    notes: string | null;
    is_in_grace_period: boolean;
    can_be_renewed: boolean;
    can_be_reactivated: boolean;
    days_until_expiration: number;
    sale_profiles?: SaleProfileRow[];
    renewals?: SaleRenewalRow[];
    transactions?: SaleTransactionRow[];
    created_at: string;
    updated_at: string | null;
}

export interface ClientOption {
    id: string;
    name: string;
    code: string | null;
    status?: string;
}

export interface PlanOption {
    id: string;
    name: string;
    code: string | null;
    service_id: string;
    service_name: string | null;
    max_profiles: number;
    capacity: SaleCapacity;
    duration_days: number;
    sale_price: string;
}

export interface AvailableProfile {
    id: string;
    number: number;
    account_id: string;
    account_code: string | null;
    account_email: string;
    service_id: string;
}

export interface ServiceOption {
    id: string;
    name: string;
    code: string | null;
}

export interface AgentOption {
    id: string;
    name: string;
}

export interface SaleMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface SaleFilters {
    code?: string;
    status?: string;
    client_id?: string;
    agent_id?: string;
    service_id?: string;
    date_from?: string;
    date_to?: string;
    expiring_soon?: string;
    limit?: number;
    offset?: number;
}

export const SALE_STATUSES: SaleStatus[] = ['active', 'expired', 'cancelled'];

export const SALE_STATUS_LABELS: Record<SaleStatus, string> = {
    active: 'Activa',
    expired: 'Expirada',
    cancelled: 'Expulsada',
};

export const SALE_CAPACITY_LABELS: Record<SaleCapacity, string> = {
    profile: 'Perfil',
    full_account: 'Cuenta completa',
};

/** Mapea el estado de la venta a la pastilla de estado del diseño. */
export const saleStatusPill = (status: SaleStatus): StatusKind => {
    const map: Record<SaleStatus, StatusKind> = {
        active: 'activo',
        expired: 'vencido',
        cancelled: 'inactivo',
    };
    return map[status];
};
