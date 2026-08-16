export type DocumentType = 'V' | 'E' | 'J' | 'P' | 'G' | 'C';

export type AddressType = 'billing' | 'shipping';

export type YesNo = 'yes' | 'no';

export interface ClientContact {
    id: string;
    client_id: string;
    name: string;
    position: string | null;
    email: string | null;
    phone: string | null;
    is_primary: YesNo;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string | null;
}

export interface ClientAddress {
    id: string;
    client_id: string;
    type: AddressType;
    name: string;
    address: string;
    city: string | null;
    state: string | null;
    country: string | null;
    route_id: string | null;
    latitude: string | null;
    longitude: string | null;
    is_default: YesNo;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string | null;
}

export interface Client {
    id: string;
    company_id: string | null;
    code: string;
    client_type_id: string | null;
    client_type_name?: string;
    price_list_id: string | null;
    price_list_name?: string;
    name: string;
    legal_name: string | null;
    document_type: DocumentType;
    document_number: string;
    phone: string | null;
    mobile: string | null;
    email: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    payment_term_days: number;
    credit_limit: string;
    credit_blocked: YesNo;
    current_balance: string;
    advance_balance: string;
    discount_percent: string;
    salesperson_id: string | null;
    salesperson_name?: string;
    route_id: string | null;
    latitude: string | null;
    longitude: string | null;
    status: 'active' | 'inactive';
    notes: string | null;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    contacts?: ClientContact[];
    addresses?: ClientAddress[];
}

export interface ClientMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface ClientFilters {
    name?: string;
    email?: string;
    phone?: string;
    code?: string;
    document_number?: string;
    document_type?: string;
    client_type_id?: string;
    salesperson_id?: string;
    credit_blocked?: string;
    status?: string;
    limit?: number;
    offset?: number;
}

/** Catálogos que alimentan los selects del formulario. */
export interface ClientOptions {
    clientTypes: Array<{ id: string; name: string }>;
    priceLists: Array<{ id: string; name: string }>;
    salespeople: Array<{ id: string; name: string }>;
}

/**
 * La naturaleza del contribuyente se deriva de la letra del RIF:
 * V, E y P son personas naturales; J, G y C son jurídicas.
 */
export const DOCUMENT_TYPE_LABELS: Record<DocumentType, string> = {
    V: 'V — Venezolano',
    E: 'E — Extranjero residente',
    J: 'J — Jurídico',
    P: 'P — Pasaporte',
    G: 'G — Gubernamental',
    C: 'C — Consejo comunal',
};

export const ADDRESS_TYPE_LABELS: Record<AddressType, string> = {
    billing: 'Facturación',
    shipping: 'Entrega',
};

/** Formatea el RIF para presentación: V-12345678. */
export function formatDocument(
    documentType: DocumentType,
    documentNumber: string,
): string {
    return `${documentType}-${documentNumber}`;
}
