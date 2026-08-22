import type { Currency } from '@/types';

/**
 * Formato y conversión de importes.
 *
 * La regla de la tasa es una sola: `rate` = cuántos bolívares vale 1 unidad de
 * la moneda. De ahí sale todo lo de aquí, y el cruce entre dos monedas
 * extranjeras se deriva pasando por el bolívar. Ninguna pantalla repite esta
 * aritmética ni declara símbolos propios.
 */

/** Importe con separadores de miles y los decimales que pide la empresa. */
export function formatAmount(
    value: string | number,
    decimals: number = 2,
): string {
    return Number(value).toLocaleString('es-VE', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

/** Importe con su símbolo delante: `Bs 36.500,00`. */
export function formatMoney(
    value: string | number,
    currency: string,
    symbol?: string | null,
    decimals: number = 2,
): string {
    return `${symbol ?? currency} ${formatAmount(value, decimals)}`;
}

/** Símbolo de una moneda del catálogo; su código si no está registrada. */
export function currencySymbol(code: string, currencies: Currency[]): string {
    return (
        currencies.find((currency) => currency.code === code)?.symbol ?? code
    );
}

/**
 * Reexpresa un importe en otra moneda cruzando por bolívares.
 *
 * `fromRate` y `toRate` son bolívares por 1 unidad de cada moneda. Devuelve
 * `null` cuando falta alguna: es preferible no mostrar el equivalente a
 * mostrar uno inventado.
 */
export function convertAmount(
    amount: number,
    fromRate: number | null | undefined,
    toRate: number | null | undefined,
): number | null {
    if (!fromRate || !toRate) {
        return null;
    }

    return (amount * fromRate) / toRate;
}
