export type DocumentType = 'V' | 'E' | 'J' | 'P' | 'G' | 'C';

export type AddressType = 'billing' | 'pickup' | 'warehouse';

export type YesNo = 'yes' | 'no';

export interface SupplierContact {
    id: string;
    supplier_id: string;
    name: string;
    position: string | null;
    email: string | null;
    phone: string | null;
    is_primary: YesNo;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string | null;
}

export interface SupplierAddress {
    id: string;
    supplier_id: string;
    type: AddressType;
    address: string;
    city: string | null;
    state: string | null;
    country: string | null;
    is_default: YesNo;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string | null;
}

export interface Supplier {
    id: string;
    company_id: string | null;
    code: string;
    supplier_type_id: string | null;
    supplier_type_name?: string;
    name: string;
    legal_name: string | null;
    document_type: DocumentType;
    document_number: string;
    email: string | null;
    phone: string | null;
    mobile: string | null;
    website: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    currency: string;
    payment_term_days: number;
    credit_limit: string;
    current_balance: string;
    advance_balance: string;
    lead_time_days: number;
    notes: string | null;
    status: 'active' | 'inactive';
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    contacts?: SupplierContact[];
    addresses?: SupplierAddress[];
}

export interface SupplierMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface SupplierFilters {
    name?: string;
    code?: string;
    document_number?: string;
    document_type?: string;
    email?: string;
    supplier_type_id?: string;
    status?: string;
    limit?: number;
    offset?: number;
}

/** Catálogos que alimentan los selects del formulario. */
export interface SupplierOptions {
    supplierTypes: Array<{ id: string; name: string }>;
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
    pickup: 'Retiro',
    warehouse: 'Almacén',
};

/** Formatea el RIF para presentación: J-12345678. */
export function formatDocument(
    documentType: DocumentType,
    documentNumber: string,
): string {
    return `${documentType}-${documentNumber}`;
}
