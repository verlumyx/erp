import type { StatusKind } from '@/components/status-pill';
import { formatMoney } from '@/lib/money';
import type { TaxOption } from '@/types/tax';

export type EntryStatus = 'draft' | 'confirmed' | 'completed' | 'cancelled';

/** De dónde viene la mercancía que se recibe. */
export type EntryType =
    | 'purchase'
    | 'production'
    | 'return'
    | 'donation'
    | 'initial'
    | 'other';

/** Cómo terminó el control de calidad de lo recibido. */
export type EntryInspectionStatus =
    | 'pending'
    | 'approved'
    | 'rejected'
    | 'partial';

/** Alias del morph map con el que viaja el documento origen. */
export const PURCHASE_ORDER = 'purchase_order';

/** Alias del morph map con el que viaja la línea origen. */
export const PURCHASE_ORDER_LINE = 'purchase_order_line';

export interface EntryLine {
    id: string;
    entry_id: string;
    line_number: number;
    item_id: string;
    item_name?: string;
    item_code?: string;
    measurement_unit_id: string;
    measurement_unit_name?: string;
    /** Línea de la orden de compra que esta línea recibe. */
    sourceable_type: string | null;
    sourceable_id: string | null;
    location_id: string | null;
    location_name?: string | null;
    lot_number: string | null;
    lot_id: string | null;
    lot_code?: string | null;
    expires_at: string | null;
    serial_numbers: string[];
    quantity: string;
    base_quantity: string;
    /** Lo aceptado y lo rechazado: solo lo primero llega al inventario. */
    received_quantity: string;
    rejected_quantity: string;
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
    /** Costo por unidad base: antes y después de prorratear los gastos. */
    unit_cost: string;
    landed_cost: string;
    rejection_reason: string | null;
    status: 'active' | 'inactive';
    notes: string | null;
}

export interface Entry {
    id: string;
    company_id: string | null;
    code: string;
    supplier_id: string | null;
    supplier_name?: string | null;
    supplier_code?: string | null;
    /** Documento origen. Vacío en una entrada sin documento previo. */
    sourceable_type: string | null;
    sourceable_id: string | null;
    sourceable_code?: string | null;
    warehouse_id: string;
    warehouse_name?: string;
    entry_date: string;
    entry_type: EntryType;
    supplier_document: string | null;
    carrier: string | null;
    tracking_number: string | null;
    received_by: string | null;
    received_by_name?: string | null;
    inspected_by: string | null;
    inspected_by_name?: string | null;
    inspection_status: EntryInspectionStatus;
    currency: string;
    exchange_rate: string;
    /** Moneda principal de la empresa congelada al registrar, con su tasa. */
    base_currency: string | null;
    base_exchange_rate: string | null;
    total_quantity: string;
    freight_amount: string;
    other_charges: string;
    total_cost: string;
    is_invoiced: 'yes' | 'no';
    cancelled_at: string | null;
    notes: string | null;
    status: EntryStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    lines?: EntryLine[];
}

export interface EntryMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface EntryFilters {
    code?: string;
    supplier_id?: string;
    warehouse_id?: string;
    entry_type?: string;
    inspection_status?: string;
    supplier_document?: string;
    is_invoiced?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

/** Una línea de la orden de origen, tal como llega en el `meta` de su opción. */
export interface PurchaseOrderOptionLine {
    id: string;
    line_number: number;
    item_id: string;
    item_code: string | null;
    item_name: string | null;
    measurement_unit_id: string;
    measurement_unit_name: string | null;
    quantity: string;
    /** Lo ya recibido: de ahí sale cuánto queda por llegar. */
    received_quantity: string;
    unit_price: string;
    discount_percent: string;
    tax_id: string | null;
    tax_percent: string;
    withholding_percent: string;
    notes: string | null;
}

/**
 * Lo que la entrada copia de la orden que la origina: viaja en el `meta` de la
 * opción que devuelve `purchase-orders.lookup`.
 */
export interface PurchaseOrderOptionMeta {
    code: string;
    supplier_id: string;
    supplier_name?: string | null;
    warehouse_id: string | null;
    currency: string;
    payment_term_days: number;
    order_date: string | null;
    status: string;
    lines?: PurchaseOrderOptionLine[];
}

/** Lo que la entrada sabe de un proveedor con solo haberlo elegido. */
export interface SupplierOptionMeta {
    code: string;
    name: string;
    currency: string;
    payment_term_days: number;
    current_balance: string;
    advance_balance: string;
    status: 'active' | 'inactive';
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
 * Ni los artículos, ni los proveedores, ni las órdenes de compra están aquí:
 * son padrones demasiado grandes para las props, y la pantalla los busca contra
 * sus endpoints de lookup con `Select2Ajax`.
 */
export interface EntryOptions {
    warehouses: Array<{ id: string; name: string; type?: string }>;
    locations: WarehouseLocationOption[];
    taxes: TaxOption[];
    receivers: Array<{ id: string; name: string }>;
}

export const STATUS_LABELS: Record<EntryStatus, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmada',
    completed: 'Cerrada',
    cancelled: 'Anulada',
};

export const TYPE_LABELS: Record<EntryType, string> = {
    purchase: 'Compra',
    production: 'Producción',
    return: 'Devolución',
    donation: 'Donación',
    initial: 'Inventario inicial',
    other: 'Otra',
};

export const INSPECTION_LABELS: Record<EntryInspectionStatus, string> = {
    pending: 'Sin inspeccionar',
    approved: 'Aprobada',
    rejected: 'Rechazada',
    partial: 'Parcial',
};

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<EntryStatus, StatusKind> = {
    draft: 'inactivo',
    confirmed: 'libre',
    completed: 'pagado',
    cancelled: 'vencido',
};

/**
 * Transiciones permitidas. Espejo de `Entry::STATUS_TRANSITIONS`: la pantalla
 * solo ofrece lo que el backend acepta.
 */
export const STATUS_TRANSITIONS: Record<EntryStatus, EntryStatus[]> = {
    draft: ['confirmed', 'cancelled'],
    confirmed: ['completed', 'cancelled'],
    completed: [],
    cancelled: [],
};

/** El tipo que exige proveedor: lo que se compra viene de alguien. */
export const SUPPLIER_TYPE: EntryType = 'purchase';

/** El tipo que no admite ni proveedor ni documento origen. */
export const INITIAL_TYPE: EntryType = 'initial';

export function isEditable(status: EntryStatus): boolean {
    return status === 'draft';
}

export function formatAmount(value: number, currency: string): string {
    return formatMoney(value, currency);
}
