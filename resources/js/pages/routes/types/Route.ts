import type { StatusKind } from '@/components/status-pill';

/**
 * La ruta es un maestro, no un documento: no se confirma ni se anula, se activa
 * y se desactiva.
 */
export type RouteStatus = 'active' | 'inactive';

/** Para qué se recorre. `mixed` entrega y cobra en la misma visita. */
export type RouteType = 'delivery' | 'collection' | 'sales' | 'mixed';

export type RouteFrequency =
    | 'daily'
    | 'weekly'
    | 'biweekly'
    | 'monthly'
    | 'on_demand';

export type Weekday = 'mon' | 'tue' | 'wed' | 'thu' | 'fri' | 'sat' | 'sun';

/** Cómo terminó una visita. Eje aparte del `status` de la fila. */
export type StopStatus =
    | 'pending'
    | 'arrived'
    | 'completed'
    | 'skipped'
    | 'failed';

/** Un cliente fijo de la ruta: la plantilla, no la visita. */
export interface RouteClient {
    id: string;
    route_id: string;
    client_id: string;
    client_name?: string;
    client_code?: string;
    client_address_id: string | null;
    client_address_name?: string | null;
    client_address?: string | null;
    sequence: number;
    status: 'active' | 'inactive';
}

/** La visita a un cliente en una fecha concreta. */
export interface RouteStop {
    id: string;
    route_id: string;
    client_id: string;
    client_name?: string;
    client_code?: string;
    client_address_id: string | null;
    client_address_name?: string | null;
    client_address?: string | null;
    stop_date: string;
    sequence: number;
    estimated_arrival: string | null;
    actual_arrival: string | null;
    actual_departure: string | null;
    stop_status: StopStatus;
    skip_reason: string | null;
    latitude: string | null;
    longitude: string | null;
    status: 'active' | 'inactive';
}

export interface Route {
    id: string;
    company_id: string | null;
    code: string;
    name: string;
    description: string | null;
    type: RouteType;
    warehouse_id: string | null;
    warehouse_name?: string | null;
    driver_id: string | null;
    driver_name?: string | null;
    salesperson_id: string | null;
    salesperson_name?: string | null;
    vehicle_plate: string | null;
    /** En cero significa «sin declarar»: no hay límite contra el que comparar. */
    vehicle_capacity_weight: string;
    vehicle_capacity_volume: string;
    frequency: RouteFrequency;
    weekdays: Weekday[];
    zone: string | null;
    city: string | null;
    estimated_duration_minutes: number;
    estimated_distance_km: string;
    notes: string | null;
    status: RouteStatus;
    created_by: string | null;
    created_at: string;
    updated_at: string | null;
    clients_count?: number;
    clients?: RouteClient[];
}

export interface RouteMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface RouteFilters {
    code?: string;
    name?: string;
    type?: string;
    frequency?: string;
    warehouse_id?: string;
    driver_id?: string;
    salesperson_id?: string;
    zone?: string;
    city?: string;
    client_id?: string;
    status?: string;
    limit?: number;
    offset?: number;
}

/**
 * Catálogos que alimentan los selects del formulario.
 *
 * Los clientes no están aquí: son el padrón más grande de esta pantalla y se
 * buscan contra `clients.lookup`. De la opción elegida salen sus direcciones,
 * así que tampoco viaja un catálogo de direcciones.
 */
export interface RouteOptions {
    warehouses: Array<{ id: string; name: string; type?: string }>;
    users: Array<{ id: string; name: string }>;
}

/** Una dirección del cliente, tal como llega en el `meta` de su opción. */
export interface ClientAddressOption {
    id: string;
    type?: string;
    name: string;
    address?: string | null;
    is_default?: 'yes' | 'no';
}

export const STATUS_LABELS: Record<RouteStatus, string> = {
    active: 'Activa',
    inactive: 'Inactiva',
};

export const TYPE_LABELS: Record<RouteType, string> = {
    delivery: 'Entrega',
    collection: 'Cobranza',
    sales: 'Preventa',
    mixed: 'Mixta',
};

export const FREQUENCY_LABELS: Record<RouteFrequency, string> = {
    daily: 'Diaria',
    weekly: 'Semanal',
    biweekly: 'Quincenal',
    monthly: 'Mensual',
    on_demand: 'Bajo demanda',
};

export const WEEKDAY_LABELS: Record<Weekday, string> = {
    mon: 'Lun',
    tue: 'Mar',
    wed: 'Mié',
    thu: 'Jue',
    fri: 'Vie',
    sat: 'Sáb',
    sun: 'Dom',
};

export const WEEKDAY_ORDER: Weekday[] = [
    'mon',
    'tue',
    'wed',
    'thu',
    'fri',
    'sat',
    'sun',
];

export const STOP_STATUS_LABELS: Record<StopStatus, string> = {
    pending: 'Pendiente',
    arrived: 'En el sitio',
    completed: 'Visitada',
    skipped: 'No visitada',
    failed: 'Fallida',
};

export const STATUS_PILL_KIND: Record<RouteStatus, StatusKind> = {
    active: 'activo',
    inactive: 'inactivo',
};

export const STOP_PILL_KIND: Record<StopStatus, StatusKind> = {
    pending: 'pendiente',
    arrived: 'libre',
    completed: 'activo',
    skipped: 'moroso',
    failed: 'vencido',
};

/** Resultados que exigen explicar por qué no se visitó al cliente. */
export const UNVISITED_STOP_STATUSES: StopStatus[] = ['skipped', 'failed'];

/** Una parada cerrada ya no se registra otra vez. */
export const CLOSED_STOP_STATUSES: StopStatus[] = [
    'completed',
    'skipped',
    'failed',
];

export function isClosed(status: StopStatus): boolean {
    return CLOSED_STOP_STATUSES.includes(status);
}

export function needsSkipReason(status: StopStatus): boolean {
    return UNVISITED_STOP_STATUSES.includes(status);
}

/** Los días de ejecución en el orden de la semana, ya traducidos. */
export function weekdayLabels(days: Weekday[]): string {
    return WEEKDAY_ORDER.filter((day) => days.includes(day))
        .map((day) => WEEKDAY_LABELS[day])
        .join(' · ');
}

/** El vehículo declaró lo que aguanta: en cero no hay límite que comparar. */
export function hasDeclaredCapacity(model: Route): boolean {
    return (
        Number(model.vehicle_capacity_weight) > 0 ||
        Number(model.vehicle_capacity_volume) > 0
    );
}
