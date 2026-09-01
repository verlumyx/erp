import type { StatusKind } from '@/components/status-pill';

/**
 * `pending_confirmation` es el anticipo aprobado cuyo cobro espejo todavía no
 * se confirma: comprometido, no recibido.
 */
export type ClientAdvanceStatus =
    | 'draft'
    | 'pending_confirmation'
    | 'confirmed'
    | 'partial'
    | 'completed'
    | 'cancelled';

export type ClientAdvancePaymentMethod =
    | 'cash'
    | 'transfer'
    | 'check'
    | 'card'
    | 'other';

export interface ClientAdvance {
    id: string;
    company_id: string | null;
    code: string;
    client_id: string;
    client_name?: string;
    client_code?: string;
    client_current_balance?: string;
    client_advance_balance?: string;
    sales_order_id: string | null;
    sales_order_code?: string | null;
    advance_date: string;
    payment_method: ClientAdvancePaymentMethod;
    reference: string | null;
    bank_account: string | null;
    currency: string;
    exchange_rate: string;
    /** Moneda principal de la empresa congelada al capturarlo, con su tasa. */
    base_currency: string | null;
    base_exchange_rate: string | null;
    amount: string;
    applied_amount: string;
    balance: string;
    refunded_amount: string;
    cancelled_at: string | null;
    notes: string | null;
    status: ClientAdvanceStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    /** El cobro espejo vivo: es el que decide si el anticipo ya se recibió. */
    collection_id?: string | null;
    collection_code?: string | null;
    collection_status?: string | null;
}

export interface ClientAdvanceMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface ClientAdvanceFilters {
    code?: string;
    client_id?: string;
    sales_order_id?: string;
    reference?: string;
    payment_method?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

/**
 * Lo que el anticipo sabe de un cliente con solo haberlo elegido: viaja en el
 * `meta` de la opción que devuelve `clients.lookup`.
 */
export interface ClientOptionMeta {
    code: string;
    name: string;
    payment_term_days: number;
    credit_blocked: string;
    current_balance: string;
    advance_balance: string;
    status: 'active' | 'inactive';
}

/**
 * Lo que el anticipo sabe del pedido que lo motiva: viaja en el `meta` de la
 * opción que devuelve `sales-orders.lookup`.
 */
export interface SalesOrderOptionMeta {
    code: string;
    client_id: string;
    client_name?: string;
    warehouse_id: string;
    currency: string;
    payment_term_days: number;
    status: string;
}

export const STATUS_LABELS: Record<ClientAdvanceStatus, string> = {
    draft: 'Borrador',
    pending_confirmation: 'Por confirmar',
    confirmed: 'Confirmado',
    partial: 'Aplicado en parte',
    completed: 'Agotado',
    cancelled: 'Anulado',
};

export const PAYMENT_METHOD_LABELS: Record<ClientAdvancePaymentMethod, string> =
    {
        cash: 'Efectivo',
        transfer: 'Transferencia',
        check: 'Cheque',
        card: 'Tarjeta',
        other: 'Otro',
    };

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<ClientAdvanceStatus, StatusKind> = {
    draft: 'inactivo',
    pending_confirmation: 'pendiente',
    confirmed: 'libre',
    partial: 'porvencer',
    completed: 'pagado',
    cancelled: 'vencido',
};

/**
 * Lo único que la pantalla puede pedir. Espejo de
 * `ClientAdvance::REQUESTABLE_STATUSES`: `confirmed` y la vuelta a `draft` los
 * mueve el cobro espejo, y `partial` / `completed` las aplicaciones a facturas.
 */
export const REQUESTABLE_TRANSITIONS: Record<
    ClientAdvanceStatus,
    ClientAdvanceStatus[]
> = {
    draft: ['pending_confirmation', 'cancelled'],
    pending_confirmation: ['cancelled'],
    confirmed: [],
    partial: [],
    completed: [],
    cancelled: [],
};

/** Etiqueta del botón que pide cada transición. */
export const TRANSITION_LABELS: Partial<Record<ClientAdvanceStatus, string>> = {
    pending_confirmation: 'Aprobar',
    cancelled: 'Anular',
};

/**
 * Un anticipo solo se edita en borrador: aprobado, su cobro espejo ya copió los
 * importes y corregirlos exige anular ese cobro.
 */
export function isEditable(advance: { status: ClientAdvanceStatus }): boolean {
    return advance.status === 'draft';
}
