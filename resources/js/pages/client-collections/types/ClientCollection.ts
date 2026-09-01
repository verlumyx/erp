import type { StatusKind } from '@/components/status-pill';

export type ClientCollectionStatus =
    | 'draft'
    | 'confirmed'
    | 'completed'
    | 'cancelled';

/** Desde dónde se inició el cobro. Se congela al crearlo. */
export type ClientCollectionOriginType = 'client' | 'invoice' | 'advance';

export type ClientCollectionMethod =
    | 'cash'
    | 'transfer'
    | 'check'
    | 'card'
    | 'advance'
    | 'credit_note'
    | 'other';

/** El cheque avanza por su propio carril, aparte del estado del documento. */
export type CheckStatus = 'pending' | 'deposited' | 'cleared' | 'bounced';

export interface ClientCollectionApplication {
    id: string;
    sales_invoice_id: string;
    sales_invoice_code?: string;
    invoice_number?: string;
    invoice_due_date?: string | null;
    invoice_total?: string;
    invoice_balance?: string;
    source_type: 'collection' | 'advance' | 'credit_note';
    source_id: string;
    applied_amount: string;
    applied_at: string | null;
    exchange_rate: string;
    /** Diferencial cambiario entre la tasa de la factura y la del cobro. */
    exchange_difference: string;
    status: 'active' | 'reversed';
}

export interface ClientCollection {
    id: string;
    company_id: string | null;
    code: string;
    client_id: string;
    client_name?: string;
    client_code?: string;
    client_current_balance?: string;
    client_advance_balance?: string;
    origin_type: ClientCollectionOriginType;
    origin_id: string | null;
    collection_date: string;
    payment_method: ClientCollectionMethod;
    reference: string | null;
    bank_account: string | null;
    collected_by: string | null;
    collected_by_name?: string;
    route_id: string | null;
    currency: string;
    exchange_rate: string;
    /** Moneda principal de la empresa congelada al cobrar, con su tasa. */
    base_currency: string | null;
    base_exchange_rate: string | null;
    amount: string;
    withholding_amount: string;
    applied_amount: string;
    unapplied_amount: string;
    /** Importe en bolívares congelado: el cobro tiene valor legal. */
    amount_ves: string;
    check_number: string | null;
    check_date: string | null;
    check_status: CheckStatus | null;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    notes: string | null;
    status: ClientCollectionStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    applications?: ClientCollectionApplication[];
}

export interface ClientCollectionMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface ClientCollectionFilters {
    code?: string;
    client_id?: string;
    reference?: string;
    payment_method?: string;
    collected_by?: string;
    check_status?: string;
    origin_type?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

/** Catálogos que la pantalla recibe en sus props. */
export interface ClientCollectionOptions {
    collectors: { id: string; name: string }[];
}

/**
 * Lo que el cobro sabe de un cliente con solo haberlo elegido: viaja en el
 * `meta` de la opción que devuelve `clients.lookup`.
 */
export interface ClientOptionMeta {
    code: string | null;
    name: string;
    payment_term_days: number;
    /** Saldo por cobrar: la suma de lo que deben sus facturas abiertas. */
    current_balance: string;
    /** Crédito a favor: sus anticipos disponibles. */
    advance_balance: string;
    status: 'active' | 'inactive';
}

/**
 * Lo que el cobro sabe de una factura: viaja en el `meta` de la opción que
 * devuelve `sales-invoices.lookup`.
 */
export interface SalesInvoiceOptionMeta {
    code: string;
    client_id: string;
    client_name?: string;
    invoice_number: string | null;
    invoice_date: string;
    due_date: string | null;
    currency: string;
    exchange_rate: string;
    total: string;
    paid_amount: string;
    balance: string;
    payment_status: string;
    status: string;
}

export const STATUS_LABELS: Record<ClientCollectionStatus, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmado',
    completed: 'Completado',
    cancelled: 'Anulado',
};

export const ORIGIN_TYPE_LABELS: Record<ClientCollectionOriginType, string> = {
    client: 'Cliente',
    invoice: 'Factura',
    advance: 'Anticipo',
};

export const PAYMENT_METHOD_LABELS: Record<ClientCollectionMethod, string> = {
    cash: 'Efectivo',
    transfer: 'Transferencia',
    check: 'Cheque',
    card: 'Tarjeta',
    advance: 'Anticipo',
    credit_note: 'Nota de crédito',
    other: 'Otro',
};

export const CHECK_STATUS_LABELS: Record<CheckStatus, string> = {
    pending: 'Sin depositar',
    deposited: 'Depositado',
    cleared: 'Conformado',
    bounced: 'Devuelto',
};

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<ClientCollectionStatus, StatusKind> = {
    draft: 'inactivo',
    confirmed: 'libre',
    completed: 'pagado',
    cancelled: 'vencido',
};

/**
 * Transiciones permitidas. Espejo de `ClientCollection::STATUS_TRANSITIONS`:
 * la pantalla solo ofrece lo que el backend acepta.
 */
export const STATUS_TRANSITIONS: Record<
    ClientCollectionStatus,
    ClientCollectionStatus[]
> = {
    draft: ['confirmed', 'cancelled'],
    confirmed: ['completed', 'cancelled'],
    completed: [],
    cancelled: [],
};

/**
 * Adónde puede ir el cheque desde donde está. Un cheque devuelto es terminal:
 * anula el cobro y ya no se mueve.
 */
export const CHECK_TRANSITIONS: Record<CheckStatus, CheckStatus[]> = {
    pending: ['deposited', 'bounced'],
    deposited: ['cleared', 'bounced'],
    cleared: [],
    bounced: [],
};

/**
 * Un cobro solo se edita en borrador, y el cobro espejo de un anticipo no se
 * edita nunca: sus importes son propiedad del anticipo.
 */
export function isEditable(collection: {
    status: ClientCollectionStatus;
    origin_type: ClientCollectionOriginType;
}): boolean {
    return (
        collection.status === 'draft' && collection.origin_type !== 'advance'
    );
}
