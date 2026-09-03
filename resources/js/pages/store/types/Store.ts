import type { StatusKind } from '@/components/status-pill';

export type YesNo = 'yes' | 'no';

export type DocumentType = 'V' | 'E' | 'J' | 'P' | 'G' | 'C';

export interface ListMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface SelectOption {
    value: string;
    label: string;
}

/* ------------------------------------------------------------------ */
/* Ajustes                                                            */
/* ------------------------------------------------------------------ */

export interface StoreSetting {
    id: string;
    company_id: string;
    is_enabled: YesNo;
    store_name: string;
    logo_path: string | null;
    logo_url: string | null;
    brand_color: string;
    price_list_id: string | null;
    price_list: { id: string; name: string } | null;
    warehouse_id: string | null;
    warehouse: { id: string; name: string } | null;
    shows_stock: YesNo;
    allows_orders: YesNo;
    default_client_type_id: string | null;
    default_client_type: { id: string; name: string } | null;
    shows_secondary_currency: YesNo;
    contact_phone: string | null;
    contact_email: string | null;
    store_url: string | null;
    has_api_key: boolean;
    api_key_last_used_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface StoreSettingOptions {
    price_lists: SelectOption[];
    warehouses: SelectOption[];
    client_types: SelectOption[];
}

/* ------------------------------------------------------------------ */
/* Publicaciones                                                      */
/* ------------------------------------------------------------------ */

export interface StoreItemImage {
    id: string;
    store_item_id: string;
    path: string;
    url: string;
    alt_text: string | null;
    order: number;
    width: number | null;
    height: number | null;
    status: 'active' | 'inactive';
}

export interface StorePrice {
    amount: string;
    currency: string;
}

export interface StoreAvailability {
    in_stock: YesNo;
    quantity?: string;
}

export interface StoreItemSource {
    id: string;
    code: string;
    sku: string | null;
    name: string;
    type: string;
    status: 'active' | 'inactive';
    is_sellable: YesNo;
    description: string | null;
    category: { id: string; name: string } | null;
}

export interface StoreItem {
    id: string;
    company_id: string | null;
    code: string;
    item_id: string;
    item: StoreItemSource | null;
    slug: string;
    title: string;
    summary: string | null;
    description: string | null;
    is_featured: YesNo;
    order: number;
    published_at: string | null;
    status: 'active' | 'inactive';
    images: StoreItemImage[];
    price: StorePrice | null;
    availability: StoreAvailability | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface StoreItemFilters {
    q?: string;
    is_featured?: string;
    status?: string;
    limit?: number;
    offset?: number;
}

/** Máximo de fotos activas por publicación. */
export const MAX_ACTIVE_IMAGES = 8;

/* ------------------------------------------------------------------ */
/* Compradores                                                        */
/* ------------------------------------------------------------------ */

export type StoreCustomerStatus = 'invited' | 'active' | 'inactive';

export type LinkSource = 'rif' | 'invitation' | 'conversion' | 'manual';

export interface StoreCustomer {
    id: string;
    company_id: string | null;
    code: string;
    client_id: string | null;
    client: {
        id: string;
        code: string;
        name: string;
        status: 'active' | 'inactive';
    } | null;
    name: string;
    email: string;
    phone: string | null;
    document_type: DocumentType | null;
    document_number: string | null;
    status: StoreCustomerStatus;
    link_source: LinkSource | null;
    linked_at: string | null;
    linked_by: string | null;
    linker: { id: string; name: string } | null;
    last_login_at: string | null;
    email_verified_at: string | null;
    invitation_expires_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface StoreCustomerFilters {
    q?: string;
    status?: string;
    linked?: string;
    limit?: number;
    offset?: number;
}

export const CUSTOMER_STATUS_LABELS: Record<StoreCustomerStatus, string> = {
    invited: 'Invitado',
    active: 'Activo',
    inactive: 'Bloqueado',
};

export const CUSTOMER_STATUS_PILL: Record<StoreCustomerStatus, StatusKind> = {
    invited: 'pendiente',
    active: 'activo',
    inactive: 'inactivo',
};

export const LINK_SOURCE_LABELS: Record<LinkSource, string> = {
    rif: 'Por RIF',
    invitation: 'Invitación',
    conversion: 'Al convertir un pedido',
    manual: 'Manual',
};

/* ------------------------------------------------------------------ */
/* Pedidos web                                                        */
/* ------------------------------------------------------------------ */

export type StoreOrderStatus = 'pending' | 'converted' | 'rejected';

export interface StoreOrderLine {
    id: string;
    line_number: number;
    item_id: string;
    item: { id: string; code: string; sku: string | null; name: string } | null;
    store_item_id: string;
    store_item: { id: string; slug: string; title: string } | null;
    measurement_unit_id: string;
    measurement_unit: {
        id: string;
        name: string;
        abbreviation: string | null;
    } | null;
    quantity: string;
    unit_price: string;
    list_price: string;
    subtotal: string;
    total: string;
    status: 'active' | 'inactive';
}

export interface StoreOrder {
    id: string;
    company_id: string | null;
    code: string;
    status: StoreOrderStatus;
    store_customer_id: string;
    store_customer: {
        id: string;
        code: string;
        name: string;
        email: string;
        client_id: string | null;
    } | null;
    client_id: string | null;
    client: {
        id: string;
        code: string;
        name: string;
        price_list_id: string | null;
        salesperson_id: string | null;
        status: 'active' | 'inactive';
        credit_blocked: YesNo;
    } | null;
    client_address_id: string | null;
    client_address: { id: string; name: string; address: string } | null;
    sales_order_id: string | null;
    sales_order: { id: string; code: string; status: string } | null;
    buyer_name: string;
    buyer_document_type: DocumentType | null;
    buyer_document_number: string | null;
    buyer_email: string;
    buyer_phone: string;
    delivery_address: string | null;
    delivery_city: string | null;
    delivery_state: string | null;
    currency: string;
    exchange_rate: string;
    subtotal: string;
    total: string;
    buyer_notes: string | null;
    notes: string | null;
    converted_by: string | null;
    converter: { id: string; name: string } | null;
    converted_at: string | null;
    rejected_at: string | null;
    rejection_reason: string | null;
    needs_review: boolean;
    lines?: StoreOrderLine[];
    created_at: string | null;
    updated_at: string | null;
}

export interface StoreOrderFilters {
    status?: string;
    q?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

export const ORDER_STATUS_LABELS: Record<StoreOrderStatus, string> = {
    pending: 'Pendiente',
    converted: 'Convertido',
    rejected: 'Rechazado',
};

export const ORDER_STATUS_PILL: Record<StoreOrderStatus, StatusKind> = {
    pending: 'pendiente',
    converted: 'pagado',
    rejected: 'vencido',
};

export const DOCUMENT_TYPE_OPTIONS: SelectOption[] = [
    { value: 'V', label: 'V — Venezolano' },
    { value: 'E', label: 'E — Extranjero residente' },
    { value: 'J', label: 'J — Jurídico' },
    { value: 'P', label: 'P — Pasaporte' },
    { value: 'G', label: 'G — Gubernamental' },
    { value: 'C', label: 'C — Consejo comunal' },
];

export function formatDocument(
    type: DocumentType | null,
    number: string | null,
): string {
    if (!type || !number) {
        return '—';
    }

    return `${type}-${number}`;
}
