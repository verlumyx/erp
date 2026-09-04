import type { StatusKind } from '@/components/status-pill';

export type SalesReturnStatus =
    | 'draft'
    | 'confirmed'
    | 'completed'
    | 'cancelled';

/** Por qué la mercancía vuelve del cliente. */
export type SalesReturnReason =
    | 'damaged'
    | 'wrong_item'
    | 'expired'
    | 'excess'
    | 'quality'
    | 'client_cancellation'
    | 'other';

/**
 * En qué estado vuelve la mercancía, y con ello a dónde va: `resalable`
 * reingresa a la bodega de venta, `damaged` a una de cuarentena y `scrap` no
 * reingresa a ninguna.
 */
export type SalesReturnCondition = 'resalable' | 'damaged' | 'scrap';

export interface SalesReturnLine {
    id: string;
    sales_return_id: string;
    line_number: number;
    item_id: string;
    item_name?: string;
    item_code?: string;
    measurement_unit_id: string;
    measurement_unit_name?: string;
    /** Bodega a la que reingresa la mercancía de esta línea. */
    warehouse_id: string;
    warehouse_name?: string | null;
    /** Línea de la factura que esta línea devuelve. */
    sales_invoice_line_id: string | null;
    lot_id: string | null;
    lot_number?: string | null;
    serial_id: string | null;
    serial_number?: string | null;
    location_id: string | null;
    location_name?: string | null;
    quantity: string;
    base_quantity: string;
    unit_price: string;
    /** Costo al que reingresa: el congelado en la venta original. */
    unit_cost: string;
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
    reason: SalesReturnReason | null;
    /** Condición propia de la línea; sin ella manda la de la cabecera. */
    condition: SalesReturnCondition | null;
    status: 'active' | 'inactive';
    notes: string | null;
}

export interface SalesReturn {
    id: string;
    company_id: string | null;
    code: string;
    client_id: string;
    client_name?: string;
    client_code?: string;
    /** Factura de origen. Vacía en una devolución sin factura previa. */
    sales_invoice_id: string | null;
    sales_invoice_code?: string | null;
    sales_invoice_number?: string | null;
    dispatch_id: string | null;
    warehouse_id: string;
    warehouse_name?: string;
    return_date: string;
    reason: SalesReturnReason;
    reason_detail: string | null;
    condition: SalesReturnCondition;
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
    /** Quién recibió físicamente la mercancía en la bodega. */
    received_by: string | null;
    received_by_name?: string | null;
    cancelled_at: string | null;
    notes: string | null;
    status: SalesReturnStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    lines?: SalesReturnLine[];
}

export interface SalesReturnMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface SalesReturnFilters {
    code?: string;
    client_id?: string;
    sales_invoice_id?: string;
    warehouse_id?: string;
    reason?: string;
    condition?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

/**
 * Lo que la devolución sabe de un cliente con solo haberlo elegido: viaja en el
 * `meta` de la opción que devuelve `clients.lookup`.
 */
export interface ClientOptionMeta {
    code: string;
    name: string;
    price_list_id: string | null;
    salesperson_id: string | null;
    payment_term_days: number;
    discount_percent: string;
    credit_blocked: 'yes' | 'no';
    current_balance: string;
    advance_balance: string;
    status: 'active' | 'inactive';
}

/** Una línea de la factura de origen, tal como llega en el `meta` de su opción. */
export interface SalesInvoiceOptionLine {
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
    /** Costo congelado de la venta; con él se valora la entrada del kardex. */
    unit_cost: string;
    discount_percent: string;
    tax_id: string | null;
    tax_percent: string;
    withholding_percent: string;
    warehouse_id: string | null;
    lot_id: string | null;
}

/**
 * Lo que la devolución copia de la factura que la origina: viaja en el `meta`
 * de la opción que devuelve `sales-invoices.lookup`.
 */
export interface SalesInvoiceOptionMeta {
    code: string;
    client_id: string;
    client_name?: string;
    invoice_number: string | null;
    invoice_date: string;
    currency: string;
    total: string;
    balance: string;
    status: string;
    lines?: SalesInvoiceOptionLine[];
}

/**
 * Catálogos que alimentan los selects del formulario.
 *
 * Ni los clientes, ni los artículos, ni las facturas están aquí: son padrones
 * demasiado grandes para las props y la pantalla los busca contra sus
 * endpoints de lookup con `Select2Ajax`.
 */
export interface SalesReturnOptions {
    warehouses: Array<{ id: string; name: string; type?: string }>;
    receivers: Array<{ id: string; name: string }>;
}

export const STATUS_LABELS: Record<SalesReturnStatus, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmada',
    completed: 'Acreditada',
    cancelled: 'Anulada',
};

export const REASON_LABELS: Record<SalesReturnReason, string> = {
    damaged: 'Mercancía dañada',
    wrong_item: 'Artículo equivocado',
    expired: 'Vencida',
    excess: 'Exceso despachado',
    quality: 'Calidad',
    client_cancellation: 'Cancelación del cliente',
    other: 'Otro',
};

export const CONDITION_LABELS: Record<SalesReturnCondition, string> = {
    resalable: 'Se puede revender',
    damaged: 'Dañada (cuarentena)',
    scrap: 'Se destruye',
};

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<SalesReturnStatus, StatusKind> = {
    draft: 'inactivo',
    confirmed: 'libre',
    completed: 'pagado',
    cancelled: 'vencido',
};

/**
 * Transiciones permitidas. Espejo de `SalesReturn::STATUS_TRANSITIONS`: la
 * pantalla solo ofrece lo que el backend acepta.
 */
export const STATUS_TRANSITIONS: Record<
    SalesReturnStatus,
    SalesReturnStatus[]
> = {
    draft: ['confirmed', 'cancelled'],
    confirmed: ['completed', 'cancelled'],
    completed: [],
    cancelled: [],
};

export function isEditable(status: SalesReturnStatus): boolean {
    return status === 'draft';
}

