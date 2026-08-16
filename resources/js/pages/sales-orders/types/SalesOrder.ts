export type SalesOrderStatus =
    | 'draft'
    | 'confirmed'
    | 'partial'
    | 'completed'
    | 'cancelled';

export interface SalesOrderLine {
    id: string;
    sales_order_id: string;
    line_number: number;
    item_id: string;
    item_name?: string;
    item_sku?: string;
    measurement_unit_id: string;
    measurement_unit_name?: string;
    quantity: string;
    base_quantity: string;
    unit_price: string;
    list_price: string;
    discount_percent: string;
    discount_amount: string;
    tax_id: string | null;
    tax_percent: string;
    tax_amount: string;
    withholding_percent: string;
    withholding_amount: string;
    subtotal: string;
    total: string;
    reserved_quantity: string;
    dispatched_quantity: string;
    invoiced_quantity: string;
    pending_quantity: string;
    status: 'active' | 'inactive';
    notes: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface SalesOrder {
    id: string;
    company_id: string | null;
    code: string;
    client_id: string;
    client_name?: string;
    client_code?: string;
    client_address_id: string | null;
    client_address_name?: string;
    warehouse_id: string;
    warehouse_name?: string;
    price_list_id: string | null;
    price_list_name?: string;
    salesperson_id: string | null;
    salesperson_name?: string;
    route_id: string | null;
    order_date: string;
    expected_date: string | null;
    client_reference: string | null;
    currency: string;
    exchange_rate: string;
    payment_term_days: number;
    subtotal: string;
    discount_amount: string;
    tax_amount: string;
    total: string;
    dispatched_percent: string;
    invoiced_percent: string;
    approved_by: string | null;
    approved_at: string | null;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    status: SalesOrderStatus;
    notes: string | null;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    lines?: SalesOrderLine[];
}

export interface SalesOrderMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface SalesOrderFilters {
    code?: string;
    client?: string;
    client_id?: string;
    warehouse_id?: string;
    salesperson_id?: string;
    client_reference?: string;
    currency?: string;
    order_date_from?: string;
    order_date_to?: string;
    status?: string;
    limit?: number;
    offset?: number;
}

export interface ClientAddressOption {
    id: string;
    type: 'billing' | 'shipping';
    name: string;
    address: string;
    is_default: 'yes' | 'no';
}

export interface ClientOption {
    id: string;
    code: string | null;
    name: string;
    price_list_id: string | null;
    payment_term_days: number;
    discount_percent: string;
    credit_blocked: 'yes' | 'no';
    addresses: ClientAddressOption[];
}

export interface ItemUnitOption {
    measurement_unit_id: string;
    name: string | null;
    is_base: 'yes' | 'no';
    conversion_factor: string;
}

export interface ItemPriceOption {
    price_list_id: string;
    price: string;
    currency: string;
}

export interface ItemOption {
    id: string;
    sku: string;
    name: string;
    min_price: string;
    units: ItemUnitOption[];
    prices: ItemPriceOption[];
}

/** Catálogos que alimentan los selects del formulario. */
export interface SalesOrderOptions {
    clients: ClientOption[];
    warehouses: Array<{
        id: string;
        code: string | null;
        name: string;
        is_default: 'yes' | 'no';
    }>;
    priceLists: Array<{ id: string; name: string }>;
    items: ItemOption[];
    salespeople: Array<{ id: string; name: string }>;
}

export const STATUS_LABELS: Record<SalesOrderStatus, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmado',
    partial: 'Parcial',
    completed: 'Completado',
    cancelled: 'Anulado',
};

/**
 * El ciclo del pedido es dirigido y debe reflejar
 * `SalesOrder::STATUS_TRANSITIONS` del backend: si divergen, el formulario
 * ofrece acciones que el request rechaza.
 */
export const STATUS_TRANSITIONS: Record<SalesOrderStatus, SalesOrderStatus[]> =
    {
        draft: ['confirmed', 'cancelled'],
        confirmed: ['partial', 'completed', 'cancelled'],
        partial: ['completed', 'cancelled'],
        completed: [],
        cancelled: [],
    };

/** Solo un borrador se edita: confirmado ya reserva inventario. */
export function isEditable(order: SalesOrder): boolean {
    return order.status === 'draft';
}

export function formatAmount(value: string | number): string {
    return Number(value).toLocaleString('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}
