import type { StatusKind } from '@/components/status-pill';

export type AccountStatus = 'active' | 'down' | 'maintenance' | 'cancelled';
export type ProfileStatus = 'available' | 'occupied' | 'maintenance';

export interface AccountServiceRef {
    id: string;
    name: string;
    code: string;
    max_profiles: number;
}

export interface Profile {
    id: string;
    account_id: string;
    number: number;
    pin: string | null;
    status: ProfileStatus;
    notes: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface ProfilesResumen {
    total: number;
    available: number;
    occupied: number;
    maintenance: number;
}

export type RenewalType = 'purchase' | 'renewal';

export interface AccountRenewal {
    id: string;
    account_id: string;
    type: RenewalType;
    amount: string;
    period_start: string;
    period_end: string;
    paid_at: string;
    notes: string | null;
    created_by?: string | null;
    created_at: string;
}

export interface Account {
    id: string;
    company_id: string;
    code: string;
    service_id: string;
    service?: AccountServiceRef | null;
    email: string;
    cost: string;
    purchase_date: string;
    next_renewal: string;
    status: AccountStatus;
    notes: string | null;
    profiles?: Profile[];
    profiles_summary?: ProfilesResumen;
    renewals?: AccountRenewal[];
    created_at: string;
    updated_at: string | null;
}

export interface AccountServiceOption {
    id: string;
    name: string;
    code: string;
    max_profiles: number;
}

export interface AccountMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface AccountFilters {
    code?: string;
    email?: string;
    status?: string;
    service_id?: string;
    limit?: number;
    offset?: number;
}

export const ACCOUNT_STATUSES: AccountStatus[] = [
    'active',
    'down',
    'maintenance',
    'cancelled',
];

export const ACCOUNT_STATUS_LABELS: Record<AccountStatus, string> = {
    active: 'Activa',
    down: 'Caída',
    maintenance: 'Mantenimiento',
    cancelled: 'Cancelada',
};

export const RENEWAL_TYPE_LABELS: Record<RenewalType, string> = {
    purchase: 'Compra',
    renewal: 'Renovación',
};

export const PROFILE_STATUSES: ProfileStatus[] = [
    'available',
    'occupied',
    'maintenance',
];

export const PROFILE_STATUS_LABELS: Record<ProfileStatus, string> = {
    available: 'Disponible',
    occupied: 'Ocupado',
    maintenance: 'Mantenimiento',
};

/** Mapea el estado de la cuenta a la pastilla de estado del diseño. */
export const accountStatusPill = (status: AccountStatus): StatusKind => {
    const map: Record<AccountStatus, StatusKind> = {
        active: 'activo',
        down: 'vencido',
        maintenance: 'pendiente',
        cancelled: 'inactivo',
    };
    return map[status];
};

/** Mapea el estado del perfil a la pastilla de estado del diseño. */
export const profileStatusPill = (status: ProfileStatus): StatusKind => {
    const map: Record<ProfileStatus, StatusKind> = {
        available: 'libre',
        occupied: 'activo',
        maintenance: 'pendiente',
    };
    return map[status];
};
