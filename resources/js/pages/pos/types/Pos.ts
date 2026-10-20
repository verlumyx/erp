export interface PosRegister {
    id: string;
    company_id: string;
    code: string;
    name: string;
    warehouse_id: string;
    warehouse_name?: string;
    price_list_id?: string | null;
    price_list_name?: string | null;
    default_client_id: string;
    default_client_name?: string;
    default_client_label?: string;
    receipt_printer_name?: string | null;
    invoice_series?: string | null;
    allow_price_override: 'yes' | 'no';
    allow_custom_discounts: 'yes' | 'no';
    max_discount_percent: number;
    notes?: string | null;
    status: 'active' | 'inactive';
    is_available?: boolean;
    active_session?: {
        id: string;
        code: string;
        opened_at: string;
        user_name?: string;
    } | null;
    created_at?: string;
    updated_at?: string;
}

export interface PosSession {
    id: string;
    company_id: string;
    code: string;
    pos_register_id: string;
    pos_register_name?: string;
    warehouse_id?: string;
    warehouse_name?: string;
    default_client_id?: string;
    default_client?: {
        id: string;
        name: string;
        tax_number?: string;
        document_type?: string;
    } | null;
    price_list_id?: string | null;
    allow_price_override?: 'yes' | 'no';
    allow_custom_discounts?: 'yes' | 'no';
    max_discount_percent?: number;
    user_id: string;
    user_name?: string;
    user_email?: string;
    opened_at: string;
    closed_at?: string | null;
    opening_cash_usd: number;
    opening_cash_ves: number;
    expected_cash_usd: number;
    expected_cash_ves: number;
    expected_card_ves: number;
    expected_mobile_ves: number;
    expected_other: number;
    declared_cash_usd?: number | null;
    declared_cash_ves?: number | null;
    declared_card_ves?: number | null;
    declared_mobile_ves?: number | null;
    declared_other?: number | null;
    difference_cash_usd?: number | null;
    difference_cash_ves?: number | null;
    sales_count: number;
    cancelled_sales_count: number;
    total_sales_amount: number;
    total_discounts_amount: number;
    supervisor_id?: string | null;
    supervisor_name?: string | null;
    closing_notes?: string | null;
    status: 'draft' | 'confirmed' | 'completed' | 'cancelled';
    created_at?: string;
    cash_movements?: Array<{
        id: string;
        movement_type: 'in' | 'out';
        concept: string;
        currency: string;
        amount: number;
        reason: string;
        authorizer_name?: string;
        created_at: string;
    }>;
    denominations?: Array<{
        id: string;
        currency: string;
        denomination: number;
        count: number;
        total_amount: number;
    }>;
}

export interface PosItem {
    id: string;
    code: string;
    sku?: string | null;
    barcode?: string | null;
    name: string;
    category_id?: string | null;
    unit_price: number;
    stock: number;
    moves_stock: boolean;
    measurement_unit_id: string;
    measurement_unit_name?: string;
    measurement_unit_symbol?: string;
    tax_id?: string | null;
    tax_percent: number;
}

export interface PosCategory {
    id: string;
    name: string;
}

export interface PosClient {
    id: string;
    name: string;
    tax_number?: string | null;
    document_type?: string | null;
    document_number?: string | null;
    phone?: string | null;
    email?: string | null;
}

export interface PosCartLine {
    id: string;
    item: PosItem;
    quantity: number;
    unit_price: number;
    discount_percent: number;
    discount_amount: number;
    tax_id?: string | null;
    tax_percent: number;
    tax_amount: number;
    subtotal: number;
    total: number;
    notes?: string;
}

export interface PosPaymentInput {
    payment_method: 'cash' | 'card_debit' | 'card_credit' | 'mobile_payment' | 'transfer' | 'biopago' | 'credit_note' | 'other';
    currency: 'USD' | 'VES' | 'EUR';
    amount_tendered: number;
    amount_applied: number;
    amount_in_document_currency: number;
    exchange_rate: number;
    reference?: string;
    card_last_digits?: string;
}

export interface PosSale {
    id: string;
    company_id: string;
    code: string;
    pos_session_id: string;
    pos_session_code?: string;
    pos_register_id: string;
    pos_register_name?: string;
    client_id: string;
    client_name?: string;
    client_tax_number?: string;
    sales_invoice_id?: string | null;
    sales_invoice_code?: string | null;
    dispatch_id?: string | null;
    dispatch_code?: string | null;
    sale_date: string;
    currency: string;
    exchange_rate: number;
    subtotal: number;
    discount_amount: number;
    tax_amount: number;
    igtf_amount: number;
    total: number;
    total_in_local_currency: number;
    paid_amount: number;
    change_amount_usd: number;
    change_amount_ves: number;
    cancelled_at?: string | null;
    cancellation_reason?: string | null;
    cancelled_by_name?: string | null;
    notes?: string | null;
    status: 'draft' | 'completed' | 'cancelled';
    lines?: Array<{
        id: string;
        line_number: number;
        item_id: string;
        item_code?: string;
        item_name?: string;
        item_barcode?: string;
        measurement_unit_name?: string;
        measurement_unit_symbol?: string;
        quantity: number;
        unit_price: number;
        discount_percent: number;
        discount_amount: number;
        tax_percent: number;
        tax_amount: number;
        subtotal: number;
        total: number;
    }>;
    payments?: Array<{
        id: string;
        payment_method: string;
        currency: string;
        exchange_rate: number;
        amount_tendered: number;
        amount_applied: number;
        amount_in_document_currency: number;
        reference?: string;
        card_last_digits?: string;
    }>;
    created_at?: string;
}

export interface PosZReport {
    type: 'X' | 'Z';
    session: {
        id: string;
        code: string;
        register_name?: string;
        warehouse_name?: string;
        cashier_name?: string;
        cashier_email?: string;
        opened_at: string;
        closed_at?: string | null;
        status: string;
        closing_notes?: string | null;
        supervisor_name?: string | null;
    };
    company: {
        id?: string;
        name?: string;
        tax_number?: string;
        address?: string;
    };
    summary: {
        sales_count: number;
        cancelled_sales_count: number;
        average_ticket: number;
        total_gross: number;
        discounts: number;
        taxable_base: number;
        total_tax: number;
        total_net: number;
    };
    payments: {
        cash_usd: number;
        cash_ves: number;
        card_ves: number;
        mobile_ves: number;
        biopago_ves?: number;
        other: number;
    };
    audit_usd: {
        opening_cash: number;
        sales_cash: number;
        cash_in: number;
        cash_out: number;
        expected: number;
        declared?: number | null;
        difference?: number | null;
        status: 'pending' | 'exact' | 'surplus' | 'deficit';
    };
    audit_ves: {
        opening_cash: number;
        sales_cash: number;
        cash_in: number;
        cash_out: number;
        expected: number;
        declared?: number | null;
        difference?: number | null;
        status: 'pending' | 'exact' | 'surplus' | 'deficit';
    };
    denominations: Array<{
        currency: string;
        denomination: number;
        count: number;
        total_amount: number;
    }>;
    cash_movements: Array<{
        id: string;
        type: 'in' | 'out';
        concept: string;
        currency: string;
        amount: number;
        reason: string;
        authorized_by?: string;
        created_at: string;
    }>;
}
