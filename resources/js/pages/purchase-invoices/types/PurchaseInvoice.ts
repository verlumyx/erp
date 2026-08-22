import type { StatusKind } from '@/components/status-pill';
import { formatMoney } from '@/lib/money';
import type { TaxOption } from '@/types/tax';

export type PurchaseInvoiceStatus =
    | 'draft'
    | 'confirmed'
    | 'completed'
    | 'cancelled';

export type PurchaseInvoicePaymentStatus =
    | 'pending'
    | 'partial'
    | 'paid'
    | 'overdue';

/** Alias del morph map admitidos como documento origen de la factura. */
export type PurchaseInvoiceSourceType = 'purchase_order';

export interface PurchaseInvoiceLine {
    id: string;
    purchase_invoice_id: string;
    line_number: number;
    item_id: string;
    item_name?: string;
    item_code?: string;
    measurement_unit_id: string;
    measurement_unit_name?: string;
    sourceable_type: string | null;
    sourceable_id: string | null;
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
    /** Costo unitario final con el flete y los gastos prorrateados. */
    landed_cost: string;
    returned_quantity: string;
    status: 'active' | 'inactive';
    notes: string | null;
}

export interface PurchaseInvoice {
    id: string;
    company_id: string | null;
    code: string;
    supplier_id: string;
    supplier_name?: string;
    supplier_code?: string;
    /** Documento origen: alias del morph map más su id. Vacíos en una factura directa. */
    sourceable_type: PurchaseInvoiceSourceType | null;
    sourceable_id: string | null;
    sourceable_code?: string | null;
    entry_id: string | null;
    warehouse_id: string;
    warehouse_name?: string;
    supplier_invoice_number: string;
    supplier_invoice_series: string | null;
    invoice_date: string;
    received_date: string | null;
    due_date: string;
    currency: string;
    exchange_rate: string;
    /** Moneda principal de la empresa congelada al emitir, con su tasa. */
    base_currency: string | null;
    base_exchange_rate: string | null;
    affects_inventory: 'yes' | 'no';
    subtotal: string;
    discount_amount: string;
    tax_amount: string;
    withholding_amount: string;
    freight_amount: string;
    other_charges: string;
    total: string;
    /** Importes en bolívares congelados: la factura tiene valor legal. */
    subtotal_ves: string;
    tax_amount_ves: string;
    total_ves: string;
    paid_amount: string;
    balance: string;
    payment_status: PurchaseInvoicePaymentStatus;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    notes: string | null;
    status: PurchaseInvoiceStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    lines?: PurchaseInvoiceLine[];
}

export interface PurchaseInvoiceMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface PurchaseInvoiceFilters {
    code?: string;
    supplier_id?: string;
    warehouse_id?: string;
    supplier_invoice_number?: string;
    status?: string;
    payment_status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

/**
 * Lo que la factura sabe de un proveedor con solo haberlo elegido: viaja en el
 * `meta` de la opción que devuelve `suppliers.lookup`.
 */
export interface SupplierOptionMeta {
    code: string;
    name: string;
    currency: string;
    payment_term_days: number;
    status: 'active' | 'inactive';
}

/**
 * Lo que la factura copia de la orden que la origina: viaja en el `meta` de la
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

/**
 * Catálogos que alimentan los selects del formulario.
 *
 * Ni los proveedores ni las órdenes de compra están aquí: son padrones
 * demasiado grandes para las props y la cabecera los busca contra sus endpoints
 * de lookup con `Select2Ajax`.
 */
export interface PurchaseInvoiceOptions {
    warehouses: Array<{ id: string; name: string }>;
    taxes: TaxOption[];
}

export const STATUS_LABELS: Record<PurchaseInvoiceStatus, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmada',
    completed: 'Completada',
    cancelled: 'Anulada',
};

export const PAYMENT_STATUS_LABELS: Record<
    PurchaseInvoicePaymentStatus,
    string
> = {
    pending: 'Pendiente',
    partial: 'Abonada',
    paid: 'Pagada',
    overdue: 'Vencida',
};

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<PurchaseInvoiceStatus, StatusKind> = {
    draft: 'inactivo',
    confirmed: 'libre',
    completed: 'pagado',
    cancelled: 'vencido',
};

export const PAYMENT_STATUS_PILL_KIND: Record<
    PurchaseInvoicePaymentStatus,
    StatusKind
> = {
    pending: 'pendiente',
    partial: 'libre',
    paid: 'pagado',
    overdue: 'vencido',
};

/**
 * Transiciones permitidas. Espejo de `PurchaseInvoice::STATUS_TRANSITIONS`:
 * la pantalla solo ofrece lo que el backend acepta.
 */
export const STATUS_TRANSITIONS: Record<
    PurchaseInvoiceStatus,
    PurchaseInvoiceStatus[]
> = {
    draft: ['confirmed', 'cancelled'],
    confirmed: ['completed', 'cancelled'],
    completed: [],
    cancelled: [],
};

export function isEditable(status: PurchaseInvoiceStatus): boolean {
    return status === 'draft';
}

export function formatAmount(value: number, currency: string): string {
    return formatMoney(value, currency);
}
