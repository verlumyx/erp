import { formatAmount as formatNumber, formatMoney } from '@/lib/money';
import type { TaxOption } from '@/types/tax';

export type SalesInvoiceStatus =
    | 'draft'
    | 'confirmed'
    | 'completed'
    | 'cancelled';

export type PaymentStatus = 'pending' | 'partial' | 'paid' | 'overdue';

export type SaleType = 'cash' | 'credit';

/** Alias del morph map con el que se guarda el documento origen. */
export type SourceType = 'sales_order';

export interface SalesInvoiceLine {
    id: string;
    sales_invoice_id: string;
    line_number: number;
    sourceable_type: string | null;
    sourceable_id: string | null;
    item_id: string;
    item_name?: string;
    item_sku?: string;
    measurement_unit_id: string;
    measurement_unit_name?: string;
    warehouse_id: string | null;
    lot_id: string | null;
    serial_id: string | null;
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
    /** Congelados al emitir: antes de eso valen 0. */
    unit_cost: string;
    total_cost: string;
    margin_amount: string;
    returned_quantity: string;
    status: 'active' | 'inactive';
    notes: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface SalesInvoice {
    id: string;
    company_id: string | null;
    code: string;
    client_id: string;
    client_name?: string;
    client_code?: string;
    /** Documento origen: alias del morph map + id. Nulos en factura directa. */
    sourceable_type: string | null;
    sourceable_id: string | null;
    sourceable_code?: string | null;
    dispatch_id: string | null;
    client_address_id: string | null;
    client_address_name?: string;
    warehouse_id: string;
    warehouse_name?: string;
    salesperson_id: string | null;
    salesperson_name?: string;
    invoice_series: string | null;
    /** Correlativo fiscal: se quema al emitir, nulo en borrador. */
    invoice_number: string | null;
    invoice_date: string;
    due_date: string;
    sale_type: SaleType;
    currency: string;
    exchange_rate: string;
    /** Moneda principal de la empresa congelada al emitir, con su tasa. */
    base_currency: string | null;
    base_exchange_rate: string | null;
    subtotal: string;
    discount_amount: string;
    tax_amount: string;
    withholding_amount: string;
    total: string;
    total_cost: string;
    /** Importes en bolívares congelados: la factura tiene valor legal. */
    subtotal_ves: string;
    tax_amount_ves: string;
    total_ves: string;
    paid_amount: string;
    balance: string;
    payment_status: PaymentStatus;
    fiscal_status: string | null;
    fiscal_uuid: string | null;
    printed_at: string | null;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    status: SalesInvoiceStatus;
    notes: string | null;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    lines?: SalesInvoiceLine[];
}

export interface SalesInvoiceMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface SalesInvoiceFilters {
    code?: string;
    client?: string;
    client_id?: string;
    warehouse_id?: string;
    salesperson_id?: string;
    invoice_number?: string;
    invoice_series?: string;
    currency?: string;
    sale_type?: string;
    payment_status?: string;
    invoice_date_from?: string;
    invoice_date_to?: string;
    due_date_from?: string;
    due_date_to?: string;
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
 * Lo que la factura sabe de un cliente con solo haberlo elegido: viaja en el
 * `meta` de la opción que devuelve `clients.lookup`.
 */
export interface ClientOptionMeta {
    code: string | null;
    name: string;
    price_list_id: string | null;
    salesperson_id: string | null;
    payment_term_days: number;
    discount_percent: string;
    credit_blocked: 'yes' | 'no';
    status: 'active' | 'inactive';
    addresses: ClientAddressOption[];
}

/**
 * Lo que la factura copia de un pedido con solo haberlo elegido: viaja en el
 * `meta` de la opción que devuelve `sales-orders.lookup`.
 *
 * Las líneas no están aquí aunque el endpoint las mande: el saldo por facturar
 * se pide aparte contra `sales-orders.invoiceable-lines`, ya calculado.
 */
export interface SalesOrderOptionMeta {
    code: string;
    client_id: string;
    client_name: string | null;
    client_address_id: string | null;
    warehouse_id: string;
    salesperson_id: string | null;
    currency: string;
    payment_term_days: number;
    status: string;
}

/**
 * Catálogos que alimentan los selects del formulario.
 *
 * Ni los clientes ni los pedidos están aquí: ambos padrones son demasiado
 * grandes para las props y se buscan contra su `lookup` con `Select2Ajax`.
 */
export interface SalesInvoiceOptions {
    warehouses: Array<{
        id: string;
        code: string | null;
        name: string;
        is_default: 'yes' | 'no';
    }>;
    salespeople: Array<{ id: string; name: string }>;
    taxes: TaxOption[];
}

export const STATUS_LABELS: Record<SalesInvoiceStatus, string> = {
    draft: 'Borrador',
    confirmed: 'Emitida',
    completed: 'Completada',
    cancelled: 'Anulada',
};

export const PAYMENT_STATUS_LABELS: Record<PaymentStatus, string> = {
    pending: 'Por cobrar',
    partial: 'Abonada',
    paid: 'Cobrada',
    overdue: 'Vencida',
};

export const SALE_TYPE_LABELS: Record<SaleType, string> = {
    cash: 'Contado',
    credit: 'Crédito',
};

/**
 * El ciclo de la factura es dirigido y debe reflejar
 * `SalesInvoice::STATUS_TRANSITIONS` del backend: si divergen, la pantalla
 * ofrece acciones que el request rechaza.
 *
 * `completed` no se ofrece: la factura se cierra sola cuando se cobra, y la
 * cobran los cobros, los anticipos y las notas de crédito.
 */
export const STATUS_TRANSITIONS: Record<
    SalesInvoiceStatus,
    SalesInvoiceStatus[]
> = {
    draft: ['confirmed', 'cancelled'],
    confirmed: ['cancelled'],
    completed: [],
    cancelled: [],
};

/** Solo un borrador se edita: emitida ya quemó su correlativo fiscal. */
export function isEditable(invoice: SalesInvoice): boolean {
    return invoice.status === 'draft';
}

/** Con `currency` antepone el código (USD 1.234,00); sin él deja solo el número. */
export function formatAmount(
    value: string | number,
    currency?: string,
): string {
    return currency ? formatMoney(value, currency) : formatNumber(value);
}
