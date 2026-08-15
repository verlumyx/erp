import { usePage } from '@inertiajs/react';
import type { SharedData, TransactionCatalog } from '@/types';

/** Lee el catálogo de tipos/categorías compartido por Inertia (fuente única backend). */
export function useTransactionCatalog(): TransactionCatalog {
    return usePage<SharedData>().props.transactionCatalog;
}

/** Etiqueta en español de una categoría. Fallback al valor crudo o "—" si es null. */
export function categoryLabel(
    catalog: TransactionCatalog,
    category: string | null,
): string {
    if (!category) {
        return '—';
    }

    return (
        catalog.categories.find((option) => option.value === category)?.label ??
        category
    );
}

/** Etiqueta en español de un tipo (income/expense). */
export function typeLabel(catalog: TransactionCatalog, type: string): string {
    return catalog.types.find((option) => option.value === type)?.label ?? type;
}
