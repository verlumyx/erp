import { usePage } from '@inertiajs/react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

    return (
        <Select value={value} onValueChange={onValueChange}>
            <SelectTrigger
                id={id}
                className={`h-[42px] w-full rounded-[10px] ${error ? 'border-bad' : ''} ${className}`}
            >
                <SelectValue placeholder="Moneda" />
            </SelectTrigger>
            <SelectContent>
                {currencies.map((currency) => (
                    <SelectItem key={currency.code} value={currency.code}>
                        {currency.name} ({currency.code})
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
