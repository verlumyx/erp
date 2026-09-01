import type { StatusKind } from '@/components/status-pill';
import { formatMoney } from '@/lib/money';
import type { TaxOption } from '@/types/tax';

export type SalesCreditNoteStatus =
    | 'draft'
    | 'confirmed'
    | 'completed'
    | 'cancelled';

/** Por qué la empresa reconoce el crédito al cliente. */
export type SalesCreditNoteReason =
    | 'return'
    | 'discount'
    | 'price_correction'
    | 'damaged'
    | 'cancellation'
    | 'other';

export interface SalesCreditNoteLine {
    id: string;
    sales_credit_note_id: string;
    line_number: number;
    item_id: string;
    item_name?: string;
    item_code?: string;
    measurement_unit_id: string;
    measurement_unit_name?: string;
    /** Línea de la factura que esta línea acredita. */
    sales_invoice_line_id: string | null;
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
    /** Costo con el que la mercancía reingresa: el mismo de la venta. */
    unit_cost: string;
    status: 'active' | 'inactive';
    notes: string | null;
}

export interface SalesCreditNote {
    id: string;
    company_id: string | null;
    code: string;
    client_id: string;
    client_name?: string;
    client_code?: string;
    /** Factura afectada. Vacía en una nota sin factura previa. */
    sales_invoice_id: string | null;
    sales_invoice_code?: string | null;
    sales_invoice_number?: string | null;
    sales_return_id: string | null;
    note_series: string | null;
    /** Correlativo fiscal: vacío mientras la nota es borrador. */
    note_number: string | null;
    note_date: string;
    reason: SalesCreditNoteReason;
    reason_detail: string | null;
    affects_inventory: 'yes' | 'no';
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
    fiscal_status: 'pending' | 'sent' | 'accepted' | 'rejected' | null;
    fiscal_uuid: string | null;
    cancelled_at: string | null;
    notes: string | null;
    status: SalesCreditNoteStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    lines?: SalesCreditNoteLine[];
}

export interface SalesCreditNoteMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface SalesCreditNoteFilters {
    code?: string;
    client_id?: string;
    sales_invoice_id?: string;
    note_number?: string;
    note_series?: string;
    reason?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

/** Una línea de la factura afectada, tal como llega en el `meta` de su opción. */
export interface SalesInvoiceOptionLine {
    id: string;
    line_number: number;
    item_id: string;
    item_code: string | null;
    item_name: string | null;
    measurement_unit_id: string;
    measurement_unit_name: string | null;
    quantity: string;
    returned_quantity: string;
    unit_price: string;
    unit_cost: string;
    discount_percent: string;
    tax_id: string | null;
    tax_percent: string;
    withholding_percent: string;
    warehouse_id: string | null;
    lot_id: string | null;
}

/**
 * Lo que la nota copia de la factura que corrige: viaja en el `meta` de la
 * opción que devuelve `sales-invoices.lookup`.
 */
export interface SalesInvoiceOptionMeta {
    code: string;
    client_id: string;
    client_name?: string | null;
    invoice_number: string | null;
    invoice_date: string;
    currency: string;
    exchange_rate: string;
    total: string;
    paid_amount: string;
    balance: string;
    status: string;
    lines?: SalesInvoiceOptionLine[];
}

/**
 * Catálogos que alimentan los selects del formulario.
 *
 * Ni los clientes, ni los artículos, ni las facturas están aquí: son padrones
 * demasiado grandes para las props y la pantalla los busca contra sus endpoints
 * de lookup con `Select2Ajax`.
 */
export interface SalesCreditNoteOptions {
    warehouses: Array<{ id: string; name: string }>;
    taxes: TaxOption[];
}

export const STATUS_LABELS: Record<SalesCreditNoteStatus, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmada',
    completed: 'Aplicada',
    cancelled: 'Anulada',
};

export const REASON_LABELS: Record<SalesCreditNoteReason, string> = {
    return: 'Devolución',
    discount: 'Descuento',
    price_correction: 'Corrección de precio',
    damaged: 'Mercancía dañada',
    cancellation: 'Anulación de la venta',
    other: 'Otro',
};

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<SalesCreditNoteStatus, StatusKind> = {
    draft: 'inactivo',
    confirmed: 'libre',
    completed: 'pagado',
    cancelled: 'vencido',
};

/**
 * Transiciones permitidas. Espejo de `SalesCreditNote::STATUS_TRANSITIONS`: la
 * pantalla solo ofrece lo que el backend acepta.
 */
export const STATUS_TRANSITIONS: Record<
    SalesCreditNoteStatus,
    SalesCreditNoteStatus[]
> = {
    draft: ['confirmed', 'cancelled'],
    confirmed: ['completed', 'cancelled'],
    completed: [],
    cancelled: [],
};

export function isEditable(status: SalesCreditNoteStatus): boolean {
    return status === 'draft';
}

export function formatAmount(value: number, currency: string): string {
    return formatMoney(value, currency);
}
