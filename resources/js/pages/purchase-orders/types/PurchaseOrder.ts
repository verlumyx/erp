import type { StatusKind } from '@/components/status-pill';
import { formatMoney } from '@/lib/money';
import type { EntryStatus } from '@/pages/entries/types/Entry';
import type { TaxOption } from '@/types/tax';

export type PurchaseOrderStatus =
    | 'draft'
    | 'confirmed'
    | 'partial'
    | 'completed'
    | 'cancelled';

export interface PurchaseOrderLine {
    id: string;
    purchase_order_id: string;
    line_number: number;
    item_id: string;
    item_name?: string;
    item_code?: string;
    measurement_unit_id: string;
    measurement_unit_name?: string;
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
    received_quantity: string;
    invoiced_quantity: string;
    pending_quantity: string;
    pending_invoiced_quantity: string;
    status: 'active' | 'inactive';
    notes: string | null;
}

export interface PurchaseOrder {
    id: string;
    company_id: string | null;
    code: string;
    supplier_id: string;
    supplier_name?: string;
    supplier_code?: string;
    warehouse_id: string;
    warehouse_name?: string;
    order_date: string;
    expected_date: string | null;
    supplier_reference: string | null;
    currency: string;
    exchange_rate: string;
    /** Moneda principal de la empresa congelada al emitir, con su tasa. */
    base_currency: string | null;
    base_exchange_rate: string | null;
    payment_term_days: number;
    subtotal: string;
    discount_amount: string;
    tax_amount: string;
    total: string;
    received_percent: string;
    invoiced_percent: string;
    approved_by: string | null;
    approved_at: string | null;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    notes: string | null;
    status: PurchaseOrderStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    lines?: PurchaseOrderLine[];
    /** Entradas que reciben la mercancía; la primera nace al aprobar la orden. */
    entries?: PurchaseOrderEntry[];
}

/** Una entrada colgada de la orden, tal como la enseña la pantalla de detalle. */
export interface PurchaseOrderEntry {
    id: string;
    code: string;
    status: EntryStatus;
}

export interface PurchaseOrderMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface PurchaseOrderFilters {
    code?: string;
    supplier_id?: string;
    warehouse_id?: string;
    supplier_reference?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

/**
 * Lo que la orden sabe de un proveedor con solo haberlo elegido: viaja en el
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
 * Catálogos que alimentan los selects del formulario.
 *
 * Los proveedores no están aquí: el padrón es demasiado grande para las props
 * y la cabecera lo busca contra `suppliers.lookup` con `Select2Ajax`.
 */
export interface PurchaseOrderOptions {
    warehouses: Array<{ id: string; name: string }>;
    taxes: TaxOption[];
}

export const STATUS_LABELS: Record<PurchaseOrderStatus, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmada',
    partial: 'Parcial',
    completed: 'Completada',
    cancelled: 'Anulada',
};

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<PurchaseOrderStatus, StatusKind> = {
    draft: 'inactivo',
    confirmed: 'libre',
    partial: 'pendiente',
    completed: 'pagado',
    cancelled: 'vencido',
};

/**
 * Transiciones permitidas. Espejo de `PurchaseOrder::STATUS_TRANSITIONS`:
 * la pantalla solo ofrece lo que el backend acepta.
 *
 * `partial` y `completed` no se ofrecen: el avance de la orden lo escriben la
 * Entrada y la Factura de compra al confirmarse o anularse, no el usuario.
 */
export const STATUS_TRANSITIONS: Record<
    PurchaseOrderStatus,
    PurchaseOrderStatus[]
> = {
    draft: ['confirmed', 'cancelled'],
    confirmed: ['cancelled'],
    partial: ['cancelled'],
    completed: [],
    cancelled: [],
};

export function isEditable(status: PurchaseOrderStatus): boolean {
    return status === 'draft';
}

export function formatAmount(value: number, currency: string): string {
    return formatMoney(value, currency);
}
