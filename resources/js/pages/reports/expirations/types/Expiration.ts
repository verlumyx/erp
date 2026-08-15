export type ExpirationStatus = 'expiring' | 'expired' | 'all';

export interface ExpirationSummary {
    expiring_count: number;
    expiring_amount: number;
    expired_count: number;
    expired_amount: number;
    renewal_rate: number;
}

export interface ExpirationMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface ExpirationFilters {
    days: number;
    status: ExpirationStatus;
    date_from: string | null;
    date_to: string | null;
    service_id: string | null;
    agent_id: string | null;
    limit?: number;
    offset?: number;
}

export interface ExpirationServiceOption {
    id: string;
    name: string;
    code: string | null;
}

export interface ExpirationAgentOption {
    id: string;
    name: string;
}

export const EXPIRATION_STATUS_LABELS: Record<ExpirationStatus, string> = {
    expiring: 'Por vencer',
    expired: 'Vencidas',
    all: 'Todas',
};
