/**
 * Impuesto del catálogo de la empresa, tal como lo ofrecen los `options` de un
 * formulario de documento.
 *
 * La retención viaja con él porque no se elige por separado: es la que el
 * impuesto practica cuando `has_withholding` es `yes`.
 */
export type TaxOption = {
    id: string;
    code: string;
    name: string;
    percentage: string;
    has_withholding: 'yes' | 'no';
    withholding_percentage: string;
};

/** Cómo se nombra un impuesto en un select: «IVA (16%)». */
export function taxOptionLabel(tax: TaxOption): string {
    return `${tax.name} (${Number(tax.percentage)}%)`;
}

/** El porcentaje que la línea retiene con ese impuesto; 0 si no retiene. */
export function taxWithholdingPercent(tax: TaxOption): number {
    return tax.has_withholding === 'yes'
        ? Number(tax.withholding_percentage)
        : 0;
}
