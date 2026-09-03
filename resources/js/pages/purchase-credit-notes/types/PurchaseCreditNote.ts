import type { StatusKind } from '@/components/status-pill';
import { formatMoney } from '@/lib/money';
import type { TaxOption } from '@/types/tax';

export type PurchaseCreditNoteStatus =
    | 'draft'
    | 'confirmed'
    | 'completed'
    | 'cancelled';

/** Por qué el proveedor reconoce el crédito. */
export type PurchaseCreditNoteReason =
    | 'return'
    | 'discount'
    | 'price_correction'
    | 'damaged'
    | 'other';

export interface PurchaseCreditNoteLine {
    id: string;
    purchase_credit_note_id: string;
    line_number: number;
    item_id: string;
    item_name?: string;
    item_code?: string;
    measurement_unit_id: string;
    measurement_unit_name?: string;
    /** Línea de la factura que esta línea acredita. */
    purchase_invoice_line_id: string | null;
    warehouse_id: string | null;
    lot_id: string | null;
    quantity: string;
    base_quantity: string;
    unit_price: string;
    discount_percent: string;
    discount_amount: string;
    tax_id: string | null;
    tax_percent: string;
    tax_amount: string;
    withholding_percent: string;
    withholding_amount: string;
    subtotal: string;
    total: string;
    status: 'active' | 'inactive';
    notes: string | null;
}

export interface PurchaseCreditNote {
    id: string;
    company_id: string | null;
    code: string;
    supplier_id: string;
    supplier_name?: string;
    supplier_code?: string;
    /** Factura afectada. Vacía en una nota sin factura previa. */
    purchase_invoice_id: string | null;
    purchase_invoice_code?: string | null;
    purchase_invoice_number?: string | null;
    purchase_return_id: string | null;
    purchase_return_code?: string | null;
    supplier_document_number: string | null;
    note_date: string;
    reason: PurchaseCreditNoteReason;
    reason_detail: string | null;
    currency: string;
    exchange_rate: string;
    /** Moneda principal de la empresa congelada al emitir, con su tasa. */
    base_currency: string | null;
    base_exchange_rate: string | null;
    subtotal: string;
    tax_amount: string;
    total: string;
    /** Importes en bolívares congelados: la nota es un documento fiscal. */
    subtotal_ves: string;
    tax_amount_ves: string;
    total_ves: string;
    applied_amount: string;
    balance: string;
    cancelled_at: string | null;
    notes: string | null;
    status: PurchaseCreditNoteStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    lines?: PurchaseCreditNoteLine[];
}

export interface PurchaseCreditNoteMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface PurchaseCreditNoteFilters {
    code?: string;
    supplier_id?: string;
    purchase_invoice_id?: string;
    supplier_document_number?: string;
    reason?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

/**
 * Lo que la nota sabe de un proveedor con solo haberlo elegido: viaja en el
 * `meta` de la opción que devuelve `suppliers.lookup`.
 */
export interface SupplierOptionMeta {
    code: string;
    name: string;
    currency: string;
    payment_term_days: number;
    status: 'active' | 'inactive';
}

/** Una línea de la factura afectada, tal como llega en el `meta` de su opción. */
export interface PurchaseInvoiceOptionLine {
    id: string;
    line_number: number;
    item_id: string;
    item_code: string | null;
    item_name: string | null;
    measurement_unit_id: string;
    measurement_unit_name: string | null;
    quantity: string;
    unit_price: string;
    discount_percent: string;
    tax_id: string | null;
    tax_percent: string;
    withholding_percent: string;
    warehouse_id: string | null;
    lot_id: string | null;
}

/**
 * Lo que la nota copia de la factura que corrige: viaja en el `meta` de la
 * opción que devuelve `purchase-invoices.lookup`.
 */
export interface PurchaseInvoiceOptionMeta {
    code: string;
    supplier_id: string;
    supplier_name?: string;
    supplier_invoice_number: string;
    invoice_date: string;
    currency: string;
    total: string;
    balance: string;
    status: string;
    lines?: PurchaseInvoiceOptionLine[];
}

/**
 * Lo que la nota sabe de la devolución que la origina: viaja en el `meta` de la
 * opción que devuelve `purchase-returns.lookup`.
 */
export interface PurchaseReturnOptionMeta {
    code: string;
    supplier_id: string;
    supplier_name?: string | null;
    purchase_invoice_id: string | null;
    return_date: string;
    reason: string;
    currency: string;
    exchange_rate: string;
    total: string;
    status: string;
}

/**
 * Catálogos que alimentan los selects del formulario.
 *
 * Ni los proveedores, ni los artículos, ni las facturas, ni las devoluciones
 * están aquí: son padrones demasiado grandes para las props y la pantalla los
 * busca contra sus endpoints de lookup con `Select2Ajax`.
 */
export interface PurchaseCreditNoteOptions {
    warehouses: Array<{ id: string; name: string }>;
    taxes: TaxOption[];
}

export const STATUS_LABELS: Record<PurchaseCreditNoteStatus, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmada',
    completed: 'Aplicada',
    cancelled: 'Anulada',
};

export const REASON_LABELS: Record<PurchaseCreditNoteReason, string> = {
    return: 'Devolución',
    discount: 'Descuento',
    price_correction: 'Corrección de precio',
    damaged: 'Mercancía dañada',
    other: 'Otro',
};

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<PurchaseCreditNoteStatus, StatusKind> = {
    draft: 'inactivo',
    confirmed: 'libre',
    completed: 'pagado',
    cancelled: 'vencido',
};

/**
 * Transiciones permitidas. Espejo de `PurchaseCreditNote::STATUS_TRANSITIONS`:
 * la pantalla solo ofrece lo que el backend acepta.
 */
export const STATUS_TRANSITIONS: Record<
    PurchaseCreditNoteStatus,
    PurchaseCreditNoteStatus[]
> = {
    draft: ['confirmed', 'cancelled'],
    confirmed: ['completed', 'cancelled'],
    completed: [],
    cancelled: [],
};

export function isEditable(status: PurchaseCreditNoteStatus): boolean {
    return status === 'draft';
}

export function formatAmount(value: number, currency: string): string {
    return formatMoney(value, currency);
}
