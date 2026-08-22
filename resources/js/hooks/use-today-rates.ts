import { usePage } from '@inertiajs/react';
import type { TodayRates } from '@/types';

interface PageProps {
    todayRates?: TodayRates | null;
    [key: string]: unknown;
}

/**
 * Tasas de hoy de la empresa activa, compartidas por Inertia en todas las
 * páginas. Sirven para lo que aún se está capturando: un documento guardado
 * lleva su propia tasa congelada y esa es la que manda.
 */
export function useTodayRates(): TodayRates {
    const { todayRates } = usePage<PageProps>().props;

    return todayRates ?? {};
}
