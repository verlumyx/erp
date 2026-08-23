import type { StatusKind } from '@/components/status-pill';

export type SupplierPaymentStatus =
    | 'draft'
    | 'confirmed'
    | 'completed'
    | 'cancelled';

/** Desde dónde se inició el pago. Se congela al crearlo. */
export type SupplierPaymentOriginType = 'supplier' | 'invoice' | 'advance';

export type SupplierPaymentMethod =
    | 'cash'
    | 'transfer'
    | 'check'
    | 'card'
    | 'advance'
    | 'credit_note'
    | 'other';

export interface SupplierPaymentApplication {
    id: string;
    purchase_invoice_id: string;
    purchase_invoice_code?: string;
    supplier_invoice_number?: string;
    invoice_due_date?: string | null;
    invoice_total?: string;
    invoice_balance?: string;
    source_type: 'payment' | 'advance' | 'credit_note';
    source_id: string;
    applied_amount: string;
    applied_at: string | null;
    exchange_rate: string;
    /** Diferencial cambiario entre la tasa de la factura y la del pago. */
    exchange_difference: string;
    status: 'active' | 'reversed';
}

export interface SupplierPayment {
    id: string;
    company_id: string | null;
    code: string;
    supplier_id: string;
    supplier_name?: string;
    supplier_code?: string;
    supplier_current_balance?: string;
    supplier_advance_balance?: string;
    origin_type: SupplierPaymentOriginType;
    origin_id: string | null;
    payment_date: string;
    payment_method: SupplierPaymentMethod;
    reference: string | null;
    bank_account: string | null;
    currency: string;
    exchange_rate: string;
    /** Moneda principal de la empresa congelada al pagar, con su tasa. */
    base_currency: string | null;
    base_exchange_rate: string | null;
    amount: string;
    withholding_amount: string;
    applied_amount: string;
    unapplied_amount: string;
    /** Importe en bolívares congelado: el pago tiene valor legal. */
    amount_ves: string;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    notes: string | null;
    status: SupplierPaymentStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    applications?: SupplierPaymentApplication[];
}

export interface SupplierPaymentMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface SupplierPaymentFilters {
    code?: string;
    supplier_id?: string;
    reference?: string;
    payment_method?: string;
    origin_type?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

/**
 * Lo que el pago sabe de un proveedor con solo haberlo elegido: viaja en el
 * `meta` de la opción que devuelve `suppliers.lookup`.
 */
export interface SupplierOptionMeta {
    code: string;
    name: string;
    currency: string;
    payment_term_days: number;
    /** Saldo por pagar: la suma de lo que deben sus facturas abiertas. */
    current_balance: string;
    /** Crédito a favor: sus anticipos disponibles. */
    advance_balance: string;
    status: 'active' | 'inactive';
}

/**
 * Lo que el pago sabe de una factura: viaja en el `meta` de la opción que
 * devuelve `purchase-invoices.lookup`.
 */
export interface PurchaseInvoiceOptionMeta {
    code: string;
    supplier_id: string;
    supplier_name?: string;
    supplier_invoice_number: string;
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

export const STATUS_LABELS: Record<SupplierPaymentStatus, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmado',
    completed: 'Completado',
    cancelled: 'Anulado',
};

export const ORIGIN_TYPE_LABELS: Record<SupplierPaymentOriginType, string> = {
    supplier: 'Proveedor',
    invoice: 'Factura',
    advance: 'Anticipo',
};

export const PAYMENT_METHOD_LABELS: Record<SupplierPaymentMethod, string> = {
    cash: 'Efectivo',
    transfer: 'Transferencia',
    check: 'Cheque',
    card: 'Tarjeta',
    advance: 'Anticipo',
    credit_note: 'Nota de crédito',
    other: 'Otro',
};

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<SupplierPaymentStatus, StatusKind> = {
    draft: 'inactivo',
    confirmed: 'libre',
    completed: 'pagado',
    cancelled: 'vencido',
};

/**
 * Transiciones permitidas. Espejo de `SupplierPayment::STATUS_TRANSITIONS`:
 * la pantalla solo ofrece lo que el backend acepta.
 */
export const STATUS_TRANSITIONS: Record<
    SupplierPaymentStatus,
    SupplierPaymentStatus[]
> = {
    draft: ['confirmed', 'cancelled'],
    confirmed: ['completed', 'cancelled'],
    completed: [],
    cancelled: [],
};

/**
 * Un pago solo se edita en borrador, y el pago espejo de un anticipo no se
 * edita nunca: sus importes son propiedad del anticipo.
 */
export function isEditable(payment: {
    status: SupplierPaymentStatus;
    origin_type: SupplierPaymentOriginType;
}): boolean {
    return payment.status === 'draft' && payment.origin_type !== 'advance';
}
