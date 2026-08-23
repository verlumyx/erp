import type { StatusKind } from '@/components/status-pill';
import { formatMoney } from '@/lib/money';
import type { TaxOption } from '@/types/tax';

export type PurchaseReturnStatus =
    | 'draft'
    | 'confirmed'
    | 'completed'
    | 'cancelled';

/** Por qué la mercancía vuelve al proveedor. */
export type PurchaseReturnReason =
    | 'damaged'
    | 'wrong_item'
    | 'expired'
    | 'excess'
    | 'quality'
    | 'other';

export interface PurchaseReturnLine {
    id: string;
    purchase_return_id: string;
    line_number: number;
    item_id: string;
    item_name?: string;
    item_code?: string;
    measurement_unit_id: string;
    measurement_unit_name?: string;
    /** Línea de la factura que esta línea devuelve. */
    purchase_invoice_line_id: string | null;
    lot_id: string | null;
    lot_number?: string | null;
    serial_id: string | null;
    serial_number?: string | null;
    location_id: string | null;
    location_name?: string | null;
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
    /** Motivo propio de la línea; sin él manda el de la cabecera. */
    reason: PurchaseReturnReason | null;
    status: 'active' | 'inactive';
    notes: string | null;
}

export interface PurchaseReturn {
    id: string;
    company_id: string | null;
    code: string;
    supplier_id: string;
    supplier_name?: string;
    supplier_code?: string;
    /** Factura de origen. Vacía en una devolución sin factura previa. */
    purchase_invoice_id: string | null;
    purchase_invoice_code?: string | null;
    purchase_invoice_number?: string | null;
    entry_id: string | null;
    warehouse_id: string;
    warehouse_name?: string;
    return_date: string;
    reason: PurchaseReturnReason;
    reason_detail: string | null;
    currency: string;
    exchange_rate: string;
    /** Moneda principal de la empresa congelada al emitir, con su tasa. */
    base_currency: string | null;
    base_exchange_rate: string | null;
    subtotal: string;
    tax_amount: string;
    total: string;
    /** Nota de crédito que acredita la devolución, si ya se emitió. */
    credit_note_id: string | null;
    credit_note_code?: string | null;
    carrier: string | null;
    tracking_number: string | null;
    cancelled_at: string | null;
    notes: string | null;
    status: PurchaseReturnStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    lines?: PurchaseReturnLine[];
}

export interface PurchaseReturnMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface PurchaseReturnFilters {
    code?: string;
    supplier_id?: string;
    purchase_invoice_id?: string;
    warehouse_id?: string;
    tracking_number?: string;
    reason?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

/**
 * Lo que la devolución sabe de un proveedor con solo haberlo elegido: viaja en
 * el `meta` de la opción que devuelve `suppliers.lookup`.
 */
export interface SupplierOptionMeta {
    code: string;
    name: string;
    currency: string;
    payment_term_days: number;
    status: 'active' | 'inactive';
}

/** Una línea de la factura de origen, tal como llega en el `meta` de su opción. */
export interface PurchaseInvoiceOptionLine {
    id: string;
    line_number: number;
    item_id: string;
    item_code: string | null;
    item_name: string | null;
    measurement_unit_id: string;
    measurement_unit_name: string | null;
    quantity: string;
    /** Lo ya devuelto: de ahí sale cuánto queda por devolver. */
    returned_quantity: string;
    unit_price: string;
    /** Costo final de la compra; con él se valora la salida del kardex. */
    landed_cost: string;
    discount_percent: string;
    tax_id: string | null;
    tax_percent: string;
    withholding_percent: string;
    warehouse_id: string | null;
    lot_id: string | null;
}

/**
 * Lo que la devolución copia de la factura que la origina: viaja en el `meta`
 * de la opción que devuelve `purchase-invoices.lookup`.
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

/** Una ubicación de bodega, tal como llega en las props del formulario. */
export interface WarehouseLocationOption {
    id: string;
    warehouse_id: string;
    name: string;
    is_default: 'yes' | 'no';
}

/**
 * Catálogos que alimentan los selects del formulario.
 *
 * Ni los proveedores, ni los artículos, ni las facturas, ni los lotes, ni las
 * series están aquí: son padrones demasiado grandes para las props y la
 * pantalla los busca contra sus endpoints de lookup con `Select2Ajax`.
 */
export interface PurchaseReturnOptions {
    warehouses: Array<{ id: string; name: string }>;
    locations: WarehouseLocationOption[];
    taxes: TaxOption[];
}

export const STATUS_LABELS: Record<PurchaseReturnStatus, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmada',
    completed: 'Acreditada',
    cancelled: 'Anulada',
};

export const REASON_LABELS: Record<PurchaseReturnReason, string> = {
    damaged: 'Mercancía dañada',
    wrong_item: 'Artículo equivocado',
    expired: 'Vencida',
    excess: 'Exceso de despacho',
    quality: 'Calidad',
    other: 'Otro',
};

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<PurchaseReturnStatus, StatusKind> = {
    draft: 'inactivo',
    confirmed: 'libre',
    completed: 'pagado',
    cancelled: 'vencido',
};

/**
 * Transiciones permitidas. Espejo de `PurchaseReturn::STATUS_TRANSITIONS`: la
 * pantalla solo ofrece lo que el backend acepta.
 */
export const STATUS_TRANSITIONS: Record<
    PurchaseReturnStatus,
    PurchaseReturnStatus[]
> = {
    draft: ['confirmed', 'cancelled'],
    confirmed: ['completed', 'cancelled'],
    completed: [],
    cancelled: [],
};

export function isEditable(status: PurchaseReturnStatus): boolean {
    return status === 'draft';
}

export function formatAmount(value: number, currency: string): string {
    return formatMoney(value, currency);
}
