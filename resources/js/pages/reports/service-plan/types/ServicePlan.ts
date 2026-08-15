export type GroupBy = 'service' | 'plan';

export interface ServicePlanRow {
    id: string;
    code: string | null;
    name: string | null;
    sales_count: number;
    revenue: number;
    avg_ticket: number;
    revenue_pct: number;
    sale_price?: number | null;
    roi_target_pct?: number | null;
}

export interface ServicePlanSummary {
    total_sales: number;
    total_revenue: number;
    avg_ticket: number;
    profile_count: number;
    full_account_count: number;
    top_label: string | null;
}

export interface ServicePlanMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface ServicePlanFilters {
    date_from: string;
    date_to: string;
    group_by: GroupBy;
    service_id: string | null;
    status: string | null;
    capacity: string | null;
    limit?: number;
    offset?: number;
}
