export interface PriceList {
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

export interface PriceListMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface PriceListFilters {
    name?: string;
    description?: string;
    code?: string;
    status?: string;
    limit?: number;
    offset?: number;
}
