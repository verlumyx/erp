import type { StatusKind } from '@/components/status-pill';

export type RefundStatus = 'pending' | 'approved' | 'rejected';

export interface RefundSaleRef {
    id: string;
    code: string;
    status: string;
    price: string;
}

export interface RefundClientRef {
    id: string;
    name: string;
    code?: string | null;
}

export interface RefundUserRef {
    id: string | null;
    name: string | null;
}

export interface RefundTransactionRow {
    id: string;
    type: string;
    category: string;
    amount: string;
    date: string | null;
    description: string;
}

export interface Refund {
    id: string;
    company_id: string;
    code: string;
    sale_id: string;
    sale?: RefundSaleRef | null;
    client_id: string;
    client?: RefundClientRef | null;
    amount: string;
    reason: string | null;
    status: RefundStatus;
    requested_by: string | null;
    requested_by_user?: RefundUserRef | null;
    resolved_by: string | null;
    resolved_by_user?: RefundUserRef | null;
    resolved_at: string | null;
    notes: string | null;
    is_pending: boolean;
    transactions?: RefundTransactionRow[];
    created_at: string;
    updated_at: string | null;
}

export interface RefundableSale {
    id: string;
    code: string;
    client_name: string | null;
    price: string;
    status: string;
}

export interface RefundMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface RefundFilters {
    q?: string;
    status?: string;
    sale_id?: string;
    limit?: number;
    offset?: number;
}

export const REFUND_STATUSES: RefundStatus[] = [
    'pending',
    'approved',
    'rejected',
];

export const REFUND_STATUS_LABELS: Record<RefundStatus, string> = {
    pending: 'Pendiente',
    approved: 'Aprobado',
    rejected: 'Rechazado',
};

/** Mapea el estado del reembolso a la pastilla de estado del diseño. */
export const refundStatusPill = (status: RefundStatus): StatusKind => {
    const map: Record<RefundStatus, StatusKind> = {
        pending: 'pendiente',
        approved: 'activo',
        rejected: 'inactivo',
    };
    return map[status];
};
