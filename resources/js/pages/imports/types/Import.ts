import type { StatusKind } from '@/components/status-pill';
import { formatMoney } from '@/lib/money';

export type ImportStatus = 'draft' | 'confirmed' | 'completed' | 'cancelled';

/** Cómo se reparte el gasto entre lo que llegó. */
export type ImportAllocationMethod = 'value' | 'quantity' | 'weight' | 'volume';

/** Por qué se cobró. */
export type ImportCostConcept =
    | 'freight'
    | 'insurance'
    | 'customs'
    | 'handling'
    | 'storage'
    | 'other';

/** Uno de los cobros que el expediente reparte. */
export interface ImportCost {
    id: string;
    import_id: string;
    line_number: number;
    sourceable_type: string | null;
    sourceable_id: string | null;
    sourceable_code?: string | null;
    supplier_id: string | null;
    supplier_name?: string | null;
    concept: ImportCostConcept;
    description: string | null;
    currency: string;
    exchange_rate: string;
    amount: string;
    /** Lo que de verdad entra al reparto, ya en la moneda del expediente. */
    converted_amount: string;
    status: 'active' | 'inactive';
    notes: string | null;
}

/** Una de las recepciones que absorben el gasto. */
export interface ImportEntry {
    id: string;
    import_id: string;
    line_number: number;
    entry_id: string;
    entry_code?: string | null;
    entry_date?: string | null;
    entry_status?: string | null;
    supplier_document?: string | null;
    total_cost?: string | null;
    status: 'active' | 'inactive';
}

/** El reparto de una de las cajas con las que llegó la línea. */
export interface ImportLineLot {
    id: string;
    import_line_id: string;
    line_number: number;
    entry_line_lot_id: string;
    lot_id: string;
    lot_number?: string | null;
    base_quantity: string;
    remaining_quantity: string;
    allocation_base: string;
    allocated_amount: string;
    unit_delta: string;
    new_unit_cost: string;
    capitalized_amount: string;
    variance_amount: string;
    status: 'active' | 'inactive';
}

/** Un ítem del expediente. No se captura: se deriva de la recepción. */
export interface ImportLine {
    id: string;
    import_id: string;
    line_number: number;
    entry_line_id: string;
    entry_code?: string | null;
    item_id: string;
    item_name?: string;
    item_code?: string;
    measurement_unit_id: string;
    measurement_unit_name?: string;
    location_id: string | null;
    location_name?: string | null;
    base_quantity: string;
    remaining_quantity: string;
    unit_cost: string;
    base_value: string;
    allocation_base: string;
    allocated_amount: string;
    unit_delta: string;
    new_unit_cost: string;
    capitalized_amount: string;
    variance_amount: string;
    status: 'active' | 'inactive';
    notes: string | null;
    lots?: ImportLineLot[];
}

export interface Import {
    id: string;
    company_id: string | null;
    code: string;
    warehouse_id: string;
    warehouse_name?: string;
    import_date: string;
    arrival_date: string | null;
    reference: string | null;
    allocation_method: ImportAllocationMethod;
    currency: string;
    exchange_rate: string;
    base_currency: string | null;
    base_exchange_rate: string | null;
    total_charges: string;
    total_base_value: string;
    total_landed_value: string;
    /** La parte del gasto que va al inventario. Estimada: la cierra el ajuste. */
    capitalized_amount: string;
    /** La que no pudo capitalizarse porque la mercancía ya salió. */
    variance_amount: string;
    adjustment_id: string | null;
    adjustment_code?: string | null;
    adjustment_status?: string | null;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    notes: string | null;
    status: ImportStatus;
    created_by: string | null;
    created_by_name?: string | null;
    created_at: string;
    updated_at: string | null;
    costs?: ImportCost[];
    entries?: ImportEntry[];
    lines?: ImportLine[];
}

export interface ImportMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface ImportFilters {
    code?: string;
    warehouse_id?: string;
    reference?: string;
    allocation_method?: string;
    currency?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

/**
 * Catálogos que alimentan los selects del formulario.
 *
 * Ni los proveedores, ni las facturas, ni las recepciones están aquí: son
 * padrones demasiado grandes para las props, y la pantalla los busca contra sus
 * endpoints con `Select2Ajax`.
 */
export interface ImportOptions {
    warehouses: Array<{ id: string; name: string; type?: string }>;
}

export const STATUS_LABELS: Record<ImportStatus, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmado',
    completed: 'Cerrado',
    cancelled: 'Anulado',
};

export const ALLOCATION_METHOD_LABELS: Record<ImportAllocationMethod, string> =
    {
        value: 'Por valor',
        quantity: 'Por cantidad',
        weight: 'Por peso',
        volume: 'Por volumen',
    };

export const CONCEPT_LABELS: Record<ImportCostConcept, string> = {
    freight: 'Flete',
    insurance: 'Seguro',
    customs: 'Aduana',
    handling: 'Manejo',
    storage: 'Almacenaje',
    other: 'Otro',
};

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<ImportStatus, StatusKind> = {
    draft: 'inactivo',
    confirmed: 'libre',
    completed: 'pagado',
    cancelled: 'vencido',
};

/**
 * Transiciones permitidas. Espejo de `Import::STATUS_TRANSITIONS`: la pantalla
 * solo ofrece lo que el backend acepta. `completed` no está: lo escribe el
 * ajuste al aplicarse, no la pantalla.
 */
export const STATUS_TRANSITIONS: Record<ImportStatus, ImportStatus[]> = {
    draft: ['confirmed', 'cancelled'],
    confirmed: ['cancelled'],
    completed: [],
    cancelled: [],
};

/** El concepto que obliga a explicarse. */
export const FREE_CONCEPT: ImportCostConcept = 'other';

export function isEditable(status: ImportStatus): boolean {
    return status === 'draft';
}

export function formatAmount(value: number, currency: string): string {
    return formatMoney(value, currency);
}
