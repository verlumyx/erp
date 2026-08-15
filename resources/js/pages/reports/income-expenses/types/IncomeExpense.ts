export type MovementType = 'income' | 'expense';

export interface Movement {
    id: string;
    company_id: string | null;
    type: MovementType;
    category: string | null;
    subcategory: string | null;
    related_type: string | null;
    related_id: string | null;
    amount: string;
    currency: string;
    date: string | null;
    payment_method: string | null;
    reference: string | null;
    period_from: string | null;
    period_to: string | null;
    description: string | null;
    notes: string | null;
    recorded_by: string | null;
    receipt_url: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface IncomeExpenseSummary {
    total_income: number;
    total_expense: number;
    balance: number;
    income_count: number;
    expense_count: number;
}

export interface IncomeExpenseMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface IncomeExpenseFilters {
    date_from: string;
    date_to: string;
    limit?: number;
    offset?: number;
}
