export interface Client {
    id: string;
    company_id: string | null;
    code: string;
    name: string;
    phone: string | null;
    email: string | null;
    status: 'active' | 'inactive';
    notes: string | null;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
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
    status?: string;
    limit?: number;
    offset?: number;
}
