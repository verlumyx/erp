/** Formato de moneda para el dashboard y vistas de datos reales. */
export function money(n: number): string {
    return (
        '$' +
        Number(n).toLocaleString('es-ES', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        })
    );
}

/** Formatea una variación porcentual con signo (p. ej. "+9,1%"). */
export function percent(n: number): string {
    const value = Number(n).toLocaleString('es-ES', {
        maximumFractionDigits: 1,
    });

    return `${n > 0 ? '+' : ''}${value}%`;
}
