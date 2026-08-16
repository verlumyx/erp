export interface MeasurementUnit {
    id: string;
    company_id: string | null;
    code: string;
    name: string;
    description: string | null;
    abbreviation: string;
    status: 'active' | 'inactive';
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface MeasurementUnitMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface MeasurementUnitFilters {
    name?: string;
    abbreviation?: string;
    code?: string;
    status?: string;
    limit?: number;
    offset?: number;
}
