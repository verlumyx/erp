import { usePage } from '@inertiajs/react';
import { Select2, type OptionType } from '@/components/ui/select2';
import type { Currency } from '@/types';

interface CurrencySelectProps {
    value: string;
    onValueChange: (value: string) => void;
    id?: string;
    error?: string;
    className?: string;
}

interface PageProps {
    currencies?: Currency[];
    [key: string]: unknown;
}

/**
 * Select de moneda. Las opciones salen del catálogo global `app_currencies`,
 * compartido por Inertia en todas las páginas: ningún formulario declara su
 * propia lista de códigos.
 */
export function useCurrencies(): Currency[] {
    const { currencies } = usePage<PageProps>().props;

    return currencies ?? [];
}

export function CurrencySelect({
    value,
    onValueChange,
    id,
    error,
    className = '',
}: CurrencySelectProps) {
    const currencies = useCurrencies();

    const options: OptionType[] = currencies.map((currency) => ({
        value: currency.code,
        label: `${currency.name} (${currency.code})`,
    }));

    return (
        <Select2
            inputId={id}
            options={options}
            value={options.find((option) => option.value === value) ?? null}
            onChange={(option) => onValueChange(option?.value ?? '')}
            error={!!error}
            size="md"
            className={className}
            placeholder="Moneda"
        />
    );
}
