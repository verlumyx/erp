export interface Service {
    id: string;
    company_id: string | null;
    code: string;
    name: string;
    logo_url: string | null;
    max_profiles: number;
    active: boolean;
    created_at: string;
    updated_at: string | null;
}

export interface ServiceMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface ServiceFilters {
    name?: string;
    code?: string;
    active?: string;
    limit?: number;
    offset?: number;
}
