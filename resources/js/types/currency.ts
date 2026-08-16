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
