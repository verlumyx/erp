import type { StatusKind } from '@/components/status-pill';
import { formatMoney } from '@/lib/money';

export type AdjustmentStatus =
    | 'draft'
    | 'pending_approval'
    | 'confirmed'
    | 'completed'
    | 'cancelled';

/** Por qué la existencia no era la que decía el sistema. */
export type AdjustmentType =
    | 'physical_count'
    | 'loss'
    | 'damage'
    | 'expiration'
    | 'theft'
    | 'correction'
    | 'revaluation'
    | 'other';

/** Qué se admite en las líneas: solo sobrantes, solo faltantes o ambos. */
export type AdjustmentDirection = 'in' | 'out' | 'mixed';

/** La dirección que el kardex le dio a una línea. */
export type AdjustmentMovementType = 'adjustment_in' | 'adjustment_out';

export interface AdjustmentLine {
    id: string;
    adjustment_id: string;
    line_number: number;
    item_id: string;
    item_name?: string;
    item_code?: string;
    measurement_unit_id: string;
    measurement_unit_name?: string;
    location_id: string | null;
    location_name?: string | null;
    lot_id: string | null;
    lot_number?: string | null;
    serial_id: string | null;
    serial_number?: string | null;
    /** Lo que decía el sistema al capturar, lo contado y la resta. */
    system_quantity: string;
    counted_quantity: string;
    difference_quantity: string;
    base_quantity: string;
    movement_type: AdjustmentMovementType;
    unit_cost: string;
    total_cost: string;
    reason: string | null;
    counted_by: string | null;
    counted_by_name?: string | null;
    status: 'active' | 'inactive';
    notes: string | null;
}

export interface Adjustment {
    id: string;
    company_id: string | null;
    code: string;
    warehouse_id: string;
    warehouse_name?: string;
    adjustment_date: string;
    type: AdjustmentType;
    direction: AdjustmentDirection;
    reason: string;
    count_id: string | null;
    total_quantity_in: string;
    total_quantity_out: string;
    total_cost_in: string;
    total_cost_out: string;
    /** Impacto en el valor del inventario: entradas menos salidas. */
    net_cost: string;
    approved_by: string | null;
    approved_by_name?: string | null;
    approved_at: string | null;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    attachment_path: string | null;
    notes: string | null;
    status: AdjustmentStatus;
    created_by: string | null;
    created_by_name?: string | null;
    created_at: string;
    updated_at: string | null;
    lines?: AdjustmentLine[];
}

export interface AdjustmentMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface AdjustmentFilters {
    code?: string;
    warehouse_id?: string;
    type?: string;
    direction?: string;
    count_id?: string;
    approved_by?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    limit?: number;
    offset?: number;
}

/** Una ubicación de bodega, tal como llega en las props del formulario. */
export interface WarehouseLocationOption {
    id: string;
    warehouse_id: string;
    name: string;
    is_default: 'yes' | 'no';
}

/**
 * Catálogos que alimentan los selects del formulario.
 *
 * Ni los artículos, ni los lotes, ni las series están aquí: son padrones
 * demasiado grandes para las props, y la pantalla los busca contra sus
 * endpoints de lookup con `Select2Ajax`.
 */
export interface AdjustmentOptions {
    warehouses: Array<{ id: string; name: string; type?: string }>;
    locations: WarehouseLocationOption[];
    counters: Array<{ id: string; name: string }>;
    /** Impacto a partir del cual el ajuste necesita una segunda firma. */
    approval_threshold: string;
}

export const STATUS_LABELS: Record<AdjustmentStatus, string> = {
    draft: 'Borrador',
    pending_approval: 'Por aprobar',
    confirmed: 'Aplicado',
    completed: 'Cerrado',
    cancelled: 'Anulado',
};

export const TYPE_LABELS: Record<AdjustmentType, string> = {
    physical_count: 'Conteo físico',
    loss: 'Merma',
    damage: 'Daño',
    expiration: 'Vencimiento',
    theft: 'Robo',
    correction: 'Error de captura',
    revaluation: 'Revaluación',
    other: 'Otro',
};

export const DIRECTION_LABELS: Record<AdjustmentDirection, string> = {
    in: 'Solo aumentos',
    out: 'Solo disminuciones',
    mixed: 'Aumentos y disminuciones',
};

/** Color de la pastilla de estado, uno por estado del documento. */
export const STATUS_PILL_KIND: Record<AdjustmentStatus, StatusKind> = {
    draft: 'inactivo',
    pending_approval: 'pendiente',
    confirmed: 'libre',
    completed: 'pagado',
    cancelled: 'vencido',
};

/**
 * Transiciones permitidas. Espejo de `Adjustment::STATUS_TRANSITIONS`: la
 * pantalla solo ofrece lo que el backend acepta.
 */
export const STATUS_TRANSITIONS: Record<AdjustmentStatus, AdjustmentStatus[]> =
    {
        draft: ['pending_approval', 'cancelled'],
        pending_approval: ['confirmed', 'cancelled'],
        confirmed: ['completed', 'cancelled'],
        completed: [],
        cancelled: [],
    };

/** El tipo que no cuenta nada: solo cambia lo que vale lo que ya está. */
export const REVALUATION_TYPE: AdjustmentType = 'revaluation';

export function isEditable(status: AdjustmentStatus): boolean {
    return status === 'draft';
}

export function formatAmount(value: number, currency: string): string {
    return formatMoney(value, currency);
}
