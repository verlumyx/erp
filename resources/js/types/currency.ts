/**
 * Moneda del catálogo global `app_currencies`.
 *
 * Llega en las props compartidas de Inertia (`currencies`), así que todo campo
 * de moneda toma sus opciones de ahí y no de una constante local.
 */
export type Currency = {
    code: string;
    name: string;
    symbol: string;
};

/**
 * Tasas de hoy de la empresa activa: bolívares que vale 1 unidad de cada
 * moneda. Llega en las props compartidas (`todayRates`) y es lo que permite
 * mostrar el equivalente de un importe que todavía no se ha guardado. Una
 * moneda sin tasa cargada no aparece en el mapa.
 */
export type TodayRates = Record<string, number>;
