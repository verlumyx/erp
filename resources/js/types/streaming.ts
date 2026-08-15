export type PlatformId =
    | 'netflix'
    | 'disney'
    | 'max'
    | 'spotify'
    | 'prime'
    | 'youtube'
    | 'crunchy'
    | 'appletv'
    | 'paramount';

export interface Platform {
    id: PlatformId;
    name: string;
    short: string;
    color: string;
    soft: string;
    slots: number;
    cost: number;
    price: number;
}

export interface CuentaDemo {
    id: string;
    plat: PlatformId;
    correo: string;
    ocupados: number;
}

export type VencimientoKey = 'activo' | 'porvencer' | 'vencido';

export interface EstadoVencimiento {
    key: VencimientoKey;
    label: string;
    dias: number;
}

export interface PerfilDemo {
    plat: PlatformId;
    cuenta: string;
    slot: string;
    precio: number;
    inicio: Date;
    venc: Date;
    estado: EstadoVencimiento;
}

export type PagoEstado = 'pagado' | 'pendiente' | 'vencido';

export interface PagoDemo {
    fecha: Date;
    monto: number;
    metodo: string;
    detalle: string;
    estado: PagoEstado;
}

export type ClienteEstado = 'activo' | 'moroso' | 'inactivo';

export interface ClienteDemo {
    id: string;
    nombre: string;
    tel: string;
    correo: string;
    ciudad: string;
    alta: Date;
    nota: string;
    perfiles: PerfilDemo[];
    pagos: PagoDemo[];
    deuda: number;
    inactivo?: boolean;
    estadoCliente: ClienteEstado;
    ingresoMensual: number;
    initials: string;
}

export interface IngresoMes {
    mes: string;
    ingreso: number;
    costo: number;
}

export interface MetricasDemo {
    ingresoMes: number;
    costoMes: number;
    gananciaMes: number;
    perfilesActivos: number;
    perfilesTotales: number;
    perfilesLibres: number;
    slotsTotales: number;
    clientesActivos: number;
    clientesTotales: number;
    porVencer: number;
    vencidos: number;
    deudaTotal: number;
    morosos: number;
}

export interface VencimientoDemo extends PerfilDemo {
    cliente: ClienteDemo;
}

/** Datos de streaming de demo asociados a un cliente real del backend. */
export interface StreamingDemo {
    perfiles: PerfilDemo[];
    pagos: PagoDemo[];
    deuda: number;
    ingresoMensual: number;
    ciudad: string;
}
