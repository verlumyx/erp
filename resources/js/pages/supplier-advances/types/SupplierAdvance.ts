import type { StatusKind } from '@/components/status-pill';

/**
 * `pending_confirmation` es el anticipo aprobado cuyo pago espejo todavía no se
 * confirma: comprometido, no entregado.
 */
export type SupplierAdvanceStatus =
    | 'draft'
    | 'pending_confirmation'
    | 'confirmed'
    | 'partial'
    | 'completed'
    | 'cancelled';

export type SupplierAdvancePaymentMethod =
    | 'cash'
    | 'transfer'
    | 'check'
    | 'card'
    | 'other';

export interface SupplierAdvance {
    id: string;
    company_id: string | null;
    code: string;
    supplier_id: string;
    supplier_name?: string;
    supplier_code?: string;
    supplier_current_balance?: string;
    supplier_advance_balance?: string;
    purchase_order_id: string | null;
    purchase_order_code?: string | null;
    advance_date: string;
    payment_method: SupplierAdvancePaymentMethod;
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
    cancelled_at: string | null;
    notes: string | null;
    status: SupplierAdvanceStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    /** El pago espejo vivo: es el que decide si el anticipo ya se entregó. */
    payment_id?: string | null;
    payment_code?: string | null;
    payment_status?: string | null;
}

export interface SupplierAdvanceMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface SupplierAdvanceFilters {
    code?: string;
    supplier_id?: string;
    purchase_order_id?: string;
    reference?: string;
    payment_method?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

/**
 * Lo que el anticipo sabe de un proveedor con solo haberlo elegido: viaja en el
 * `meta` de la opción que devuelve `suppliers.lookup`.
 */
export interface SupplierOptionMeta {
    code: string;
    name: string;
    currency: string;
    payment_term_days: number;
    current_balance: string;
    advance_balance: string;
    status: 'active' | 'inactive';
}

/**
 * Lo que el anticipo sabe de la orden que lo motiva: viaja en el `meta` de la
 * opción que devuelve `purchase-orders.lookup`.
 */
export interface PurchaseOrderOptionMeta {
    code: string;
    supplier_id: string;
    supplier_name?: string;
    warehouse_id: string;
    currency: string;
    payment_term_days: number;
    order_date: string;
    status: string;
}

export const STATUS_LABELS: Record<SupplierAdvanceStatus, string> = {
    draft: 'Borrador',
    pending_confirmation: 'Por confirmar',
    confirmed: 'Confirmado',
    partial: 'Aplicado en parte',
    completed: 'Agotado',
    cancelled: 'Anulado',
};

export const PAYMENT_METHOD_LABELS: Record<
    SupplierAdvancePaymentMethod,
    string
> = {
    cash: 'Efectivo',
    transfer: 'Transferencia',
    check: 'Cheque',
    card: 'Tarjeta',
    other: 'Otro',
};

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<SupplierAdvanceStatus, StatusKind> = {
    draft: 'inactivo',
    pending_confirmation: 'pendiente',
    confirmed: 'libre',
    partial: 'porvencer',
    completed: 'pagado',
    cancelled: 'vencido',
};

/**
 * Lo único que la pantalla puede pedir. Espejo de
 * `SupplierAdvance::REQUESTABLE_STATUSES`: `confirmed` y la vuelta a `draft`
 * los mueve el pago espejo, y `partial` / `completed` las aplicaciones a
 * facturas.
 */
export const REQUESTABLE_TRANSITIONS: Record<
    SupplierAdvanceStatus,
    SupplierAdvanceStatus[]
> = {
    draft: ['pending_confirmation', 'cancelled'],
    pending_confirmation: ['cancelled'],
    confirmed: [],
    partial: [],
    completed: [],
    cancelled: [],
};

/** Etiqueta del botón que pide cada transición. */
export const TRANSITION_LABELS: Partial<Record<SupplierAdvanceStatus, string>> =
    {
        pending_confirmation: 'Aprobar',
        cancelled: 'Anular',
    };

/**
 * Un anticipo solo se edita en borrador: aprobado, su pago espejo ya copió los
 * importes y corregirlos exige anular ese pago.
 */
export function isEditable(advance: {
    status: SupplierAdvanceStatus;
}): boolean {
    return advance.status === 'draft';
}
