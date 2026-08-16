export type ExchangeRateType = 'legal' | 'manual';

export interface ExchangeRate {
    id: string;
    company_id: string | null;
    code: string;
    /** Código ISO 4217; las opciones salen del catálogo global de monedas. */
    currency: string;
    rate_date: string;
    rate: string;
    type: ExchangeRateType;
    source: string | null;
    description: string | null;
    status: 'active' | 'inactive';
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface ExchangeRateMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface ExchangeRateFilters {
    code?: string;
    currency?: string;
    rate_date?: string;
    type?: string;
    status?: string;
    limit?: number;
    offset?: number;
}

/** Decimales de `app_exchange_rates.rate` — decimal(18,8). */
export const RATE_DECIMALS = 8;

export const TYPE_LABELS: Record<ExchangeRateType, string> = {
    legal: 'Legal',
    manual: 'Manual',
};
