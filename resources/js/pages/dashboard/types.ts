export interface DashboardMetrics {
    income_month: number;
    expense_month: number;
    net_profit: number;
    profit_margin_pct: number;
    income_trend_pct: number | null;
    profit_trend_pct: number | null;
    active_profiles: number;
    free_profiles: number;
    total_profiles: number;
    receivable_amount: number;
    receivable_clients: number;
}

export interface DashboardRevenuePoint {
    month: string;
    income: number;
    expense: number;
    profit: number;
}

export interface DashboardOccupancy {
    occupied: number;
    available: number;
    maintenance: number;
    total: number;
}

export interface DashboardPlatform {
    id: string;
    name: string;
    occupied: number;
}

export type ExpirationStatus = 'vencido' | 'porvencer' | 'activo';

export interface DashboardExpiration {
    id: string;
    code: string;
    client_name: string;
    client_phone: string | null;
    service_name: string;
    status_key: ExpirationStatus;
    days: number;
}
