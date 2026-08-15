import type { StatusKind } from '@/components/status-pill';

export type ManualTransactionStatus = 'pending' | 'approved' | 'cancelled';

export const MANUAL_TRANSACTION_STATUS_LABELS: Record<
    ManualTransactionStatus,
    string
> = {
    pending: 'Pendiente',
    approved: 'Aprobado',
    cancelled: 'Cancelado',
};

/** Mapea el estado a la pastilla de estado del diseño. */
export const manualTransactionStatusPill = (
    status: ManualTransactionStatus,
): StatusKind => {
    const map: Record<ManualTransactionStatus, StatusKind> = {
        pending: 'pendiente',
        approved: 'activo',
        cancelled: 'inactivo',
    };
    return map[status];
};

export interface ManualTransactionUserRef {
    id: string | null;
    name: string | null;
}

export interface ManualTransactionLine {
    id: string;
    type: 'income' | 'expense';
    category: string;
    amount: string;
    description: string | null;
}

export interface ManualTransaction {
    id: string;
    company_id: string;
    code: string;
    date: string | null;
    payment_method: string;
    reference: string | null;
    currency: string;
    description: string | null;
    notes: string | null;
    total: string;
    status: ManualTransactionStatus;
    approved_at: string | null;
    cancelled_at: string | null;
    is_pending: boolean;
    can_be_approved: boolean;
    can_be_cancelled: boolean;
    recorded_by: string | null;
    recorded_by_user?: ManualTransactionUserRef | null;
    lines?: ManualTransactionLine[];
    created_at: string;
    updated_at: string | null;
}

/** Línea editable en el formulario (antes de persistir). */
export interface ManualTransactionLineDraft {
    category: string;
    amount: number;
    description: string;
}

export interface ManualTransactionMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface ManualTransactionFilters {
    code?: string;
    reference?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}
