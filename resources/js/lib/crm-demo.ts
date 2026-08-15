/**
 * StreamCRM — datos de ejemplo (DEMO).
 *
 * El backend de plataformas/cuentas/perfiles/pagos aún no existe; este módulo
 * provee datos realistas para el dashboard y para enriquecer los clientes
 * reales con perfiles/pagos de demostración. Cuando exista el backend, las
 * páginas deben pasar a consumir props de Inertia y este archivo se elimina.
 *
 * Las fechas se expresan como desplazamientos en días respecto de "hoy" para
 * que los estados (activo / por vencer / vencido) se mantengan realistas.
 */
import type {
    ClienteDemo,
    CuentaDemo,
    EstadoVencimiento,
    IngresoMes,
    MetricasDemo,
    PagoDemo,
    PerfilDemo,
    Platform,
    PlatformId,
    StreamingDemo,
    VencimientoDemo,
} from '@/types/streaming';

// ---- Plataformas (colores de marca usados como código visual, sin logos) ----
export const PLATFORMS: Record<PlatformId, Platform> = {
    netflix: {
        id: 'netflix',
        name: 'Netflix',
        short: 'N',
        color: '#e50914',
        soft: '#fdecec',
        slots: 5,
        cost: 14990,
        price: 4500,
    },
    disney: {
        id: 'disney',
        name: 'Disney+',
        short: 'D+',
        color: '#1942d6',
        soft: '#e8edfd',
        slots: 7,
        cost: 9990,
        price: 3000,
    },
    max: {
        id: 'max',
        name: 'Max',
        short: 'M',
        color: '#6b21d6',
        soft: '#f0e9fc',
        slots: 5,
        cost: 8990,
        price: 3000,
    },
    spotify: {
        id: 'spotify',
        name: 'Spotify',
        short: 'S',
        color: '#1aa64b',
        soft: '#e6f6ec',
        slots: 6,
        cost: 7990,
        price: 2500,
    },
    prime: {
        id: 'prime',
        name: 'Prime Video',
        short: 'P',
        color: '#00a8e1',
        soft: '#e3f6fd',
        slots: 6,
        cost: 6490,
        price: 2500,
    },
    youtube: {
        id: 'youtube',
        name: 'YouTube Premium',
        short: 'YT',
        color: '#f01616',
        soft: '#fdeaea',
        slots: 5,
        cost: 11990,
        price: 3500,
    },
    crunchy: {
        id: 'crunchy',
        name: 'Crunchyroll',
        short: 'CR',
        color: '#f47521',
        soft: '#fef0e6',
        slots: 4,
        cost: 5990,
        price: 2500,
    },
    appletv: {
        id: 'appletv',
        name: 'Apple TV+',
        short: 'TV',
        color: '#1c1c1e',
        soft: '#ececed',
        slots: 6,
        cost: 6990,
        price: 2800,
    },
    paramount: {
        id: 'paramount',
        name: 'Paramount+',
        short: 'P+',
        color: '#0064ff',
        soft: '#e5eeff',
        slots: 6,
        cost: 5490,
        price: 2500,
    },
};

// ---- Helpers de fecha y formato ----
const HOY = new Date();
HOY.setHours(0, 0, 0, 0);

function enDias(dias: number): Date {
    const d = new Date(HOY);
    d.setDate(d.getDate() + dias);
    return d;
}

export function diasEntre(a: Date, b: Date): number {
    return Math.round((a.getTime() - b.getTime()) / 86400000);
}

const MESES = [
    'ene',
    'feb',
    'mar',
    'abr',
    'may',
    'jun',
    'jul',
    'ago',
    'sep',
    'oct',
    'nov',
    'dic',
];

export function fmtFecha(date: Date): string {
    return `${date.getDate()} ${MESES[date.getMonth()]} ${date.getFullYear()}`;
}

export function clp(n: number): string {
    return '$' + n.toLocaleString('es-CL');
}

export function estadoPorVenc(venc: Date): EstadoVencimiento {
    const dias = diasEntre(venc, HOY);
    if (dias < 0) {
        return { key: 'vencido', label: 'Vencido', dias };
    }
    if (dias <= 5) {
        return { key: 'porvencer', label: 'Por vencer', dias };
    }
    return { key: 'activo', label: 'Activo', dias };
}

export function soloDigitos(tel: string): string {
    return tel.replace(/[^0-9]/g, '');
}

export function whatsappUrl(tel: string): string {
    return `https://wa.me/${soloDigitos(tel)}`;
}

/** Meses (aprox.) transcurridos desde una fecha hasta hoy, mínimo 1. */
export function mesesDesde(fecha: string | Date): number {
    return Math.max(1, Math.round(diasEntre(HOY, new Date(fecha)) / 30));
}

export function iniciales(nombre: string): string {
    return nombre
        .split(' ')
        .map((w) => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

// ---- Cuentas (pool de inventario) ----
export const CUENTAS: CuentaDemo[] = [
    {
        id: 'c1',
        plat: 'netflix',
        correo: 'nflx.pool01@streamhub.cl',
        ocupados: 5,
    },
    {
        id: 'c2',
        plat: 'netflix',
        correo: 'nflx.pool02@streamhub.cl',
        ocupados: 3,
    },
    {
        id: 'c3',
        plat: 'disney',
        correo: 'dsny.pool01@streamhub.cl',
        ocupados: 6,
    },
    { id: 'c4', plat: 'max', correo: 'max.pool01@streamhub.cl', ocupados: 4 },
    {
        id: 'c5',
        plat: 'spotify',
        correo: 'spot.pool01@streamhub.cl',
        ocupados: 6,
    },
    {
        id: 'c6',
        plat: 'prime',
        correo: 'prime.pool01@streamhub.cl',
        ocupados: 4,
    },
    {
        id: 'c7',
        plat: 'youtube',
        correo: 'ytp.pool01@streamhub.cl',
        ocupados: 5,
    },
    {
        id: 'c8',
        plat: 'crunchy',
        correo: 'cr.pool01@streamhub.cl',
        ocupados: 2,
    },
    {
        id: 'c9',
        plat: 'appletv',
        correo: 'atv.pool01@streamhub.cl',
        ocupados: 3,
    },
    {
        id: 'c10',
        plat: 'paramount',
        correo: 'pmt.pool01@streamhub.cl',
        ocupados: 4,
    },
    {
        id: 'c11',
        plat: 'disney',
        correo: 'dsny.pool02@streamhub.cl',
        ocupados: 5,
    },
    { id: 'c12', plat: 'max', correo: 'max.pool02@streamhub.cl', ocupados: 5 },
];

// ---- Clientes de demostración ----
function perfil(
    plat: PlatformId,
    cuenta: string,
    slot: string,
    inicioOffset: number,
    vencOffset: number,
): PerfilDemo {
    const venc = enDias(vencOffset);
    return {
        plat,
        cuenta,
        slot,
        precio: PLATFORMS[plat].price,
        inicio: enDias(inicioOffset),
        venc,
        estado: estadoPorVenc(venc),
    };
}

function pago(
    fechaOffset: number,
    monto: number,
    metodo: string,
    detalle: string,
    estado: PagoDemo['estado'],
): PagoDemo {
    return { fecha: enDias(fechaOffset), monto, metodo, detalle, estado };
}

type ClienteDemoBase = Omit<
    ClienteDemo,
    'estadoCliente' | 'ingresoMensual' | 'initials'
>;

const CLIENTES_BASE: ClienteDemoBase[] = [
    {
        id: 'demo-cl1',
        nombre: 'Camila Rojas',
        tel: '+56 9 8123 4521',
        correo: 'camila.rojas@gmail.com',
        ciudad: 'Santiago',
        alta: enDias(-268),
        nota: 'Cliente puntual. Prefiere pago por transferencia los días 1.',
        perfiles: [
            perfil('netflix', 'c1', 'Perfil 2', -29, 2),
            perfil('spotify', 'c5', 'Camila', -18, 13),
        ],
        pagos: [
            pago(
                -29,
                7000,
                'Transferencia',
                'Netflix + Spotify mes pasado',
                'pagado',
            ),
            pago(
                -59,
                7000,
                'Transferencia',
                'Netflix + Spotify mes anterior',
                'pagado',
            ),
        ],
        deuda: 0,
    },
    {
        id: 'demo-cl2',
        nombre: 'Matías Fuentes',
        tel: '+56 9 7345 1190',
        correo: 'mati.fuentes@outlook.com',
        ciudad: 'Valparaíso',
        alta: enDias(-216),
        nota: 'Pidió Max para toda la familia.',
        perfiles: [
            perfil('max', 'c4', 'Perfil 1', -34, -3),
            perfil('disney', 'c3', 'Mati', -34, -3),
            perfil('prime', 'c6', 'Perfil 3', -10, 21),
        ],
        pagos: [
            pago(-34, 5500, 'Efectivo', 'Max + Disney mes pasado', 'pagado'),
            pago(-3, 2500, '—', 'Prime este mes', 'pendiente'),
        ],
        deuda: 2500,
    },
    {
        id: 'demo-cl3',
        nombre: 'Valentina Soto',
        tel: '+56 9 6622 8841',
        correo: 'vale.soto@gmail.com',
        ciudad: 'Concepción',
        alta: enDias(-140),
        nota: '',
        perfiles: [perfil('netflix', 'c1', 'Perfil 4', -36, -5)],
        pagos: [
            pago(-36, 4500, 'Transferencia', 'Netflix mes pasado', 'pagado'),
        ],
        deuda: 4500,
    },
    {
        id: 'demo-cl4',
        nombre: 'Benjamín Torres',
        tel: '+56 9 9011 2233',
        correo: 'benja.torres@gmail.com',
        ciudad: 'Santiago',
        alta: enDias(-320),
        nota: 'Revende a sus compañeros de trabajo.',
        perfiles: [
            perfil('youtube', 'c7', 'Perfil 1', -13, 18),
            perfil('netflix', 'c2', 'Perfil 1', -27, 4),
            perfil('crunchy', 'c8', 'Benja', -24, 7),
        ],
        pagos: [pago(-27, 10500, 'Transferencia', 'Pack 3 perfiles', 'pagado')],
        deuda: 0,
    },
    {
        id: 'demo-cl5',
        nombre: 'Francisca Vera',
        tel: '+56 9 5544 7788',
        correo: 'fran.vera@hotmail.com',
        ciudad: 'La Serena',
        alta: enDias(-118),
        nota: 'Atrasada hace 2 meses. Enviar recordatorio.',
        perfiles: [perfil('disney', 'c3', 'Fran', -62, -32)],
        pagos: [
            pago(-62, 3000, 'Transferencia', 'Disney hace dos meses', 'pagado'),
            pago(-32, 3000, '—', 'Disney mes pasado', 'vencido'),
        ],
        deuda: 3000,
    },
    {
        id: 'demo-cl6',
        nombre: 'Joaquín Méndez',
        tel: '+56 9 4433 9922',
        correo: 'joaco.mendez@gmail.com',
        ciudad: 'Santiago',
        alta: enDias(-188),
        nota: '',
        perfiles: [
            perfil('spotify', 'c5', 'Joaco', -8, 23),
            perfil('max', 'c12', 'Perfil 2', -8, 23),
        ],
        pagos: [pago(-8, 5500, 'Efectivo', 'Spotify + Max', 'pagado')],
        deuda: 0,
    },
    {
        id: 'demo-cl7',
        nombre: 'Antonia Lagos',
        tel: '+56 9 3322 1100',
        correo: 'anto.lagos@gmail.com',
        ciudad: 'Rancagua',
        alta: enDias(-85),
        nota: 'Quiere agregar Paramount+.',
        perfiles: [
            perfil('paramount', 'c10', 'Anto', -22, 9),
            perfil('appletv', 'c9', 'Perfil 2', -22, 9),
        ],
        pagos: [
            pago(-22, 5300, 'Transferencia', 'Paramount + Apple TV', 'pagado'),
        ],
        deuda: 0,
    },
    {
        id: 'demo-cl8',
        nombre: 'Diego Salas',
        tel: '+56 9 2211 0099',
        correo: 'diego.salas@gmail.com',
        ciudad: 'Antofagasta',
        alta: enDias(-245),
        nota: '',
        perfiles: [
            perfil('netflix', 'c2', 'Perfil 2', -35, -4),
            perfil('prime', 'c6', 'Perfil 1', -35, -4),
        ],
        pagos: [
            pago(
                -35,
                7000,
                'Transferencia',
                'Netflix + Prime mes pasado',
                'pagado',
            ),
            pago(-4, 7000, '—', 'Netflix + Prime este mes', 'pendiente'),
        ],
        deuda: 7000,
    },
    {
        id: 'demo-cl9',
        nombre: 'Isidora Pinto',
        tel: '+56 9 8877 6655',
        correo: 'isi.pinto@gmail.com',
        ciudad: 'Santiago',
        alta: enDias(-66),
        nota: 'Nueva. Captada por Instagram.',
        perfiles: [perfil('netflix', 'c2', 'Perfil 3', -16, 15)],
        pagos: [pago(-16, 4500, 'Transferencia', 'Netflix', 'pagado')],
        deuda: 0,
    },
    {
        id: 'demo-cl10',
        nombre: 'Tomás Araya',
        tel: '+56 9 7766 5544',
        correo: 'tomas.araya@gmail.com',
        ciudad: 'Temuco',
        alta: enDias(-292),
        nota: 'Cliente desde el principio. VIP.',
        perfiles: [
            perfil('max', 'c4', 'Perfil 2', -30, 1),
            perfil('disney', 'c11', 'Tomás', -30, 1),
            perfil('spotify', 'c5', 'Tomi', -30, 1),
            perfil('youtube', 'c7', 'Perfil 2', -30, 1),
        ],
        pagos: [pago(-30, 12500, 'Transferencia', 'Pack 4 perfiles', 'pagado')],
        deuda: 0,
    },
    {
        id: 'demo-cl11',
        nombre: 'Catalina Núñez',
        tel: '+56 9 6655 4433',
        correo: 'cata.nunez@gmail.com',
        ciudad: 'Iquique',
        alta: enDias(-100),
        nota: '',
        perfiles: [perfil('crunchy', 'c8', 'Cata', -38, -8)],
        pagos: [
            pago(-38, 2500, 'Efectivo', 'Crunchyroll hace dos meses', 'pagado'),
            pago(-8, 2500, '—', 'Crunchyroll mes pasado', 'vencido'),
        ],
        deuda: 2500,
    },
    {
        id: 'demo-cl12',
        nombre: 'Sebastián Reyes',
        tel: '+56 9 5566 7788',
        correo: 'seba.reyes@gmail.com',
        ciudad: 'Santiago',
        alta: enDias(-189),
        nota: 'Inactivo desde hace meses. No renovó.',
        perfiles: [],
        pagos: [pago(-87, 4500, 'Transferencia', 'Netflix', 'pagado')],
        deuda: 0,
        inactivo: true,
    },
    {
        id: 'demo-cl13',
        nombre: 'Martina Cruz',
        tel: '+56 9 4455 6677',
        correo: 'marti.cruz@gmail.com',
        ciudad: 'Puerto Montt',
        alta: enDias(-151),
        nota: '',
        perfiles: [
            perfil('prime', 'c6', 'Marti', -11, 20),
            perfil('paramount', 'c10', 'Perfil 3', -11, 20),
        ],
        pagos: [
            pago(-11, 5000, 'Transferencia', 'Prime + Paramount', 'pagado'),
        ],
        deuda: 0,
    },
    {
        id: 'demo-cl14',
        nombre: 'Felipe Gómez',
        tel: '+56 9 3344 5566',
        correo: 'feli.gomez@gmail.com',
        ciudad: 'Santiago',
        alta: enDias(-255),
        nota: '',
        perfiles: [
            perfil('netflix', 'c1', 'Perfil 5', -32, -1),
            perfil('appletv', 'c9', 'Feli', -32, -1),
        ],
        pagos: [pago(-32, 7300, 'Efectivo', 'Netflix + Apple TV', 'pagado')],
        deuda: 0,
    },
];

export const DEMO_CLIENTES: ClienteDemo[] = CLIENTES_BASE.map((cl) => ({
    ...cl,
    estadoCliente: cl.inactivo
        ? 'inactivo'
        : cl.deuda > 0
          ? 'moroso'
          : 'activo',
    ingresoMensual: cl.perfiles.reduce((s, p) => s + p.precio, 0),
    initials: iniciales(cl.nombre),
}));

// ---- Serie de ingresos últimos 6 meses (etiquetas relativas a hoy) ----
const MESES_CORTOS = [
    'Ene',
    'Feb',
    'Mar',
    'Abr',
    'May',
    'Jun',
    'Jul',
    'Ago',
    'Sep',
    'Oct',
    'Nov',
    'Dic',
];
const VALORES_6M = [
    { ingreso: 312000, costo: 168000 },
    { ingreso: 348500, costo: 174000 },
    { ingreso: 366000, costo: 182000 },
    { ingreso: 401500, costo: 191000 },
    { ingreso: 438000, costo: 197000 },
    { ingreso: 286500, costo: 142000 },
];

export const INGRESOS_MES: IngresoMes[] = VALORES_6M.map((v, i) => ({
    mes: MESES_CORTOS[(HOY.getMonth() - 5 + i + 12) % 12],
    ...v,
}));

export const MES_ANTERIOR =
    MESES_CORTOS[(HOY.getMonth() + 11) % 12].toLowerCase();

// ---- Métricas derivadas para el dashboard ----
const todosPerfiles: VencimientoDemo[] = DEMO_CLIENTES.flatMap((cl) =>
    cl.perfiles.map((p) => ({ ...p, cliente: cl })),
);
const totalSlots = CUENTAS.reduce((s, c) => s + PLATFORMS[c.plat].slots, 0);
const slotsOcupados = CUENTAS.reduce((s, c) => s + c.ocupados, 0);

export const METRICAS: MetricasDemo = {
    ingresoMes: INGRESOS_MES[4].ingreso,
    costoMes: INGRESOS_MES[4].costo,
    gananciaMes: INGRESOS_MES[4].ingreso - INGRESOS_MES[4].costo,
    perfilesActivos: todosPerfiles.filter((p) => p.estado.key !== 'vencido')
        .length,
    perfilesTotales: todosPerfiles.length,
    perfilesLibres: totalSlots - slotsOcupados,
    slotsTotales: totalSlots,
    clientesActivos: DEMO_CLIENTES.filter((c) => c.estadoCliente === 'activo')
        .length,
    clientesTotales: DEMO_CLIENTES.length,
    porVencer: todosPerfiles.filter((p) => p.estado.key === 'porvencer').length,
    vencidos: todosPerfiles.filter((p) => p.estado.key === 'vencido').length,
    deudaTotal: DEMO_CLIENTES.reduce((s, c) => s + c.deuda, 0),
    morosos: DEMO_CLIENTES.filter((c) => c.estadoCliente === 'moroso').length,
};

// Perfiles por plataforma (para barras del dashboard)
export const POR_PLATAFORMA = Object.values(PLATFORMS)
    .map((pl) => ({
        ...pl,
        cuenta: todosPerfiles.filter((p) => p.plat === pl.id).length,
    }))
    .filter((x) => x.cuenta > 0)
    .sort((a, b) => b.cuenta - a.cuenta);

// Próximos vencimientos (ordenados)
export const VENCIMIENTOS: VencimientoDemo[] = todosPerfiles
    .filter((p) => p.estado.key !== 'activo' || p.estado.dias <= 9)
    .sort((a, b) => a.venc.getTime() - b.venc.getTime())
    .slice(0, 8);

// ---- Enriquecimiento de clientes reales con datos de streaming DEMO ----
function hashId(id: string): number {
    let h = 0;
    for (const c of id) {
        h = (h * 31 + c.charCodeAt(0)) >>> 0;
    }
    return h;
}

/**
 * Asocia datos de streaming de demostración a un cliente real del backend de
 * forma determinista (mismo cliente → mismos perfiles entre renders).
 */
export function demoStreamingFor(clientId: string): StreamingDemo {
    const base = DEMO_CLIENTES[hashId(clientId) % DEMO_CLIENTES.length];
    return {
        perfiles: base.perfiles,
        pagos: base.pagos,
        deuda: base.deuda,
        ingresoMensual: base.ingresoMensual,
        ciudad: base.ciudad,
    };
}
