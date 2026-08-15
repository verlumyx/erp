export interface Company {
    id: string;
    name: string;
    status: 'active' | 'inactive';
    description: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface CompanyMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface CompanyFilters {
    name?: string;
    status?: string;
    limit?: number;
    offset?: number;
}
