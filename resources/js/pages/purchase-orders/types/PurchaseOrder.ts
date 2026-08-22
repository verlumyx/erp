import { formatMoney } from '@/lib/money';

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
    status: 'active' | 'inactive';
    notes: string | null;
}

export interface PurchaseOrder {
    id: string;
    company_id: string | null;
    code: string;
    supplier_id: string;
    supplier_name?: string;
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

export interface PurchaseOrderItemUnitOption {
    measurement_unit_id: string;
    name: string;
    is_base: 'yes' | 'no';
    conversion_factor: string;
}

export interface PurchaseOrderItemOption {
    id: string;
    code: string;
    name: string;
    standard_cost: string;
    units: PurchaseOrderItemUnitOption[];
}

/** Catálogos que alimentan los selects del formulario. */
export interface PurchaseOrderOptions {
    suppliers: Array<{
        id: string;
        code: string;
        name: string;
        currency: string;
        payment_term_days: number;
    }>;
    warehouses: Array<{ id: string; name: string }>;
    items: PurchaseOrderItemOption[];
}

import type { StatusKind } from '@/components/status-pill';

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
 */
export const STATUS_TRANSITIONS: Record<
    PurchaseOrderStatus,
    PurchaseOrderStatus[]
> = {
    draft: ['confirmed', 'cancelled'],
    confirmed: ['partial', 'completed', 'cancelled'],
    partial: ['completed', 'cancelled'],
    completed: [],
    cancelled: [],
};

export function isEditable(status: PurchaseOrderStatus): boolean {
    return status === 'draft';
}

export function formatAmount(value: number, currency: string): string {
    return formatMoney(value, currency);
}
