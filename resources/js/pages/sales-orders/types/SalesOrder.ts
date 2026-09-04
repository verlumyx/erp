import { formatAmount as formatNumber, formatMoney } from '@/lib/money';
import type { DispatchStatus } from '@/pages/dispatches/types/Dispatch';
import type { TaxOption } from '@/types/tax';

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
    /** Moneda principal de la empresa congelada al emitir, con su tasa. */
    base_currency: string | null;
    base_exchange_rate: string | null;
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
    /** Despachos que sacan la mercancía; el primero nace al aprobar el pedido. */
    dispatches?: SalesOrderDispatch[];
}

/** Un despacho colgado del pedido, tal como lo enseña la pantalla de detalle. */
export interface SalesOrderDispatch {
    id: string;
    code: string;
    status: DispatchStatus;
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

/**
 * Lo que el pedido sabe de un cliente con solo haberlo elegido: viaja en el
 * `meta` de la opción que devuelve `clients.lookup`.
 */
export interface ClientOptionMeta {
    code: string | null;
    name: string;
    price_list_id: string | null;
    /** Vendedor asignado al cliente: el pedido nace con él. */
    salesperson_id: string | null;
    payment_term_days: number;
    discount_percent: string;
    credit_blocked: 'yes' | 'no';
    status: 'active' | 'inactive';
    addresses: ClientAddressOption[];
}

/**
 * Catálogos que alimentan los selects del formulario.
 *
 * Los clientes no están aquí: la cartera es demasiado grande para las props y
 * la cabecera la busca contra `clients.lookup` con `Select2Ajax`.
 */
export interface SalesOrderOptions {
    warehouses: Array<{
        id: string;
        code: string | null;
        name: string;
        is_default: 'yes' | 'no';
    }>;
    priceLists: Array<{ id: string; name: string }>;
    salespeople: Array<{ id: string; name: string }>;
    taxes: TaxOption[];
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
 *
 * `partial` y `completed` no se ofrecen: el avance del pedido lo escriben el
 * Despacho y la Factura de venta al confirmarse o anularse, no el usuario.
 */
export const STATUS_TRANSITIONS: Record<SalesOrderStatus, SalesOrderStatus[]> =
    {
        draft: ['confirmed', 'cancelled'],
        confirmed: ['cancelled'],
        partial: ['cancelled'],
        completed: [],
        cancelled: [],
    };

/** Solo un borrador se edita: confirmado ya reserva inventario. */
export function isEditable(order: SalesOrder): boolean {
    return order.status === 'draft';
}

/** Con `currency` antepone el código (USD 1.234,00); sin él deja solo el número. */
export function formatAmount(
    value: string | number,
    currency?: string,
): string {
    return currency ? formatMoney(value, currency) : formatNumber(value);
}
