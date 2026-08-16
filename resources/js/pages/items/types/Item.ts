export type ItemType =
    | 'inventoried'
    | 'non_inventoried'
    | 'service'
    | 'kit'
    | 'serialized';

export type CostMethod = 'average' | 'fifo' | 'standard';

export type YesNo = 'yes' | 'no';

export interface ItemUnit {
    id: string;
    item_id: string;
    measurement_unit_id: string;
    measurement_unit_name?: string;
    measurement_unit_abbreviation?: string;
    is_base: YesNo;
    conversion_factor: string;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string | null;
}

export interface ItemPrice {
    id: string;
    item_id: string;
    price_list_id: string;
    price_list_name?: string;
    price: string;
    currency: string;
    valid_from: string | null;
    valid_to: string | null;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string | null;
}

export interface Item {
    id: string;
    company_id: string | null;
    code: string;
    sku: string;
    barcode: string | null;
    name: string;
    description: string | null;
    type: ItemType;
    category_id: string | null;
    category_name?: string;
    sale_tax_id: string | null;
    purchase_tax_id: string | null;
    cost_method: CostMethod;
    standard_cost: string;
    average_cost: string;
    min_price: string;
    is_purchasable: YesNo;
    is_sellable: YesNo;
    min_stock: string;
    max_stock: string;
    reorder_quantity: string;
    weight: string;
    volume: string;
    image_path: string | null;
    notes: string | null;
    status: 'active' | 'inactive';
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    units?: ItemUnit[];
    prices?: ItemPrice[];
}

export interface ItemMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface ItemFilters {
    name?: string;
    sku?: string;
    barcode?: string;
    code?: string;
    type?: string;
    category_id?: string;
    status?: string;
    limit?: number;
    offset?: number;
}

/** Catálogos que alimentan los selects del formulario. */
export interface ItemOptions {
    categories: Array<{ id: string; name: string }>;
    measurementUnits: Array<{
        id: string;
        name: string;
        abbreviation: string;
    }>;
    priceLists: Array<{ id: string; name: string }>;
}

export const ITEM_TYPE_LABELS: Record<ItemType, string> = {
    inventoried: 'Inventariado',
    non_inventoried: 'No inventariado',
    service: 'Servicio',
    kit: 'Kit',
    serialized: 'Serializado',
};

export const COST_METHOD_LABELS: Record<CostMethod, string> = {
    average: 'Promedio ponderado',
    fifo: 'FIFO',
    standard: 'Costo estándar',
};
