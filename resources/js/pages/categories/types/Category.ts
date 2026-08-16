export interface Category {
    id: string;
    company_id: string | null;
    code: string;
    name: string;
    description: string | null;
    order: number;
    status: 'active' | 'inactive';
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface CategoryMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface CategoryFilters {
    name?: string;
    description?: string;
    code?: string;
    status?: string;
    limit?: number;
    offset?: number;
}
