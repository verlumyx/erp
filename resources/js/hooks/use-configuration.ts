import { usePage } from '@inertiajs/react';
import type { Configuration } from '@/types';

interface PageProps {
    configuration?: Configuration | null;
    [key: string]: unknown;
}

/**
 * Configuración de la empresa activa, compartida por Inertia en todas las
 * páginas. Es la fuente de la moneda principal: ninguna pantalla la asume.
 *
 * Devuelve `null` mientras no haya empresa seleccionada (login, error, etc.).
 */
export function useConfiguration(): Configuration | null {
    const { configuration } = usePage<PageProps>().props;

    return configuration ?? null;
}
