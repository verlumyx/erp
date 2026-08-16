export interface ClientType {
    id: string;
    company_id: string | null;
    code: string;
    name: string;
    description: string | null;
    status: 'active' | 'inactive';
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface ClientTypeMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface ClientTypeFilters {
    name?: string;
    code?: string;
    status?: string;
    limit?: number;
    offset?: number;
}
