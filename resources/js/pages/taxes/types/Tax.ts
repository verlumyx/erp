export type YesNo = 'yes' | 'no';

export interface Tax {
    id: string;
    company_id: string | null;
    code: string;
    name: string;
    description: string | null;
    percentage: string;
    has_withholding: YesNo;
    withholding_percentage: string;
    status: 'active' | 'inactive';
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface TaxMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface TaxFilters {
    name?: string;
    code?: string;
    has_withholding?: string;
    status?: string;
    limit?: number;
    offset?: number;
}

/** Decimales con los que se guarda y se muestra un porcentaje. */
export const PERCENTAGE_DECIMALS = 4;

/**
 * Muestra el porcentaje sin ceros de relleno: 15.0000 → "15", 7.5000 → "7,5".
 */
export function formatPercentage(value: string | number): string {
    const parsed = Number(value);

    if (Number.isNaN(parsed)) {
        return String(value);
    }

    return parsed
        .toLocaleString('es-VE', { maximumFractionDigits: PERCENTAGE_DECIMALS })
        .toString();
}
