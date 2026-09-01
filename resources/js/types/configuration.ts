/**
 * Configuración de la empresa activa.
 *
 * Llega en las props compartidas de Inertia (`configuration`), así que
 * cualquier pantalla sabe en qué moneda trabajar sin pedírsela a su
 * controlador.
 */
export type Configuration = {
    id: string;
    company_id: string;
    /** Moneda en la que la empresa lleva sus cifras. */
    base_currency: string;
    /** Moneda de presentación obligatoria; `null` la desactiva. */
    secondary_currency: string | null;
    /** Resuelto en el backend: si es `false` no hay nada que convertir. */
    dual_currency: boolean;
    rate_type: 'legal' | 'manual';
    /** Campo sí/no: el proyecto no usa columnas booleanas. */
    allows_rate_override: 'yes' | 'no';
    amount_decimals: number;
    price_decimals: number;
    /**
     * Impacto en el valor del inventario a partir del cual un ajuste necesita
     * la firma de alguien distinto de quien lo registró.
     */
    adjustment_approval_threshold: string;
    created_at: string | null;
    updated_at: string | null;
};
