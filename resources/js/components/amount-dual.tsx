import { useCurrencies } from '@/components/currency-select';
import { useConfiguration } from '@/hooks/use-configuration';
import { useTodayRates } from '@/hooks/use-today-rates';
import { convertAmount, currencySymbol, formatMoney } from '@/lib/money';

interface AmountDualProps {
    /** Importe en la moneda del documento. */
    amount: string | number;
    /** Moneda del documento. */
    currency: string;
    /**
     * Tasa congelada del documento: bolívares por 1 unidad de `currency`. Sin
     * ella se usa la de hoy, que es lo correcto para un importe que todavía se
     * está capturando.
     */
    rate?: string | number | null;
    /**
     * Moneda principal de la empresa congelada en el documento, con su tasa.
     * Solo se usan si la empresa presenta sus importes en esa misma moneda:
     * son las que permiten reexpresar el documento aunque la empresa cambie de
     * moneda mañana.
     */
    baseCurrency?: string | null;
    baseRate?: string | number | null;
    className?: string;
}

/**
 * Un importe con su equivalente debajo.
 *
 * Es el único sitio donde se decide si un importe sale en una línea o en dos:
 * las pantallas no comparan monedas. Con `dual_currency` en `false` —la
 * empresa lleva sus cifras en la misma moneda de presentación— sale una sola
 * línea. Si falta alguna tasa tampoco hay segunda línea: antes ningún
 * equivalente que uno inventado.
 */
export function AmountDual({
    amount,
    currency,
    rate,
    baseCurrency,
    baseRate,
    className = '',
}: AmountDualProps) {
    const configuration = useConfiguration();
    const currencies = useCurrencies();
    const todayRates = useTodayRates();

    const decimals = configuration?.amount_decimals ?? 2;
    const primary = formatMoney(
        amount,
        currency,
        currencySymbol(currency, currencies),
        decimals,
    );

    const secondaryCurrency = configuration?.secondary_currency ?? null;

    if (!configuration?.dual_currency || secondaryCurrency === null) {
        return <span className={className}>{primary}</span>;
    }

    /**
     * El bolívar vale 1 y no lleva tasa; cualquier otra moneda de presentación
     * usa la congelada del documento cuando es la de la empresa, y la de hoy
     * mientras el importe todavía se captura.
     */
    const secondaryRate =
        baseCurrency === secondaryCurrency && baseRate != null
            ? Number(baseRate)
            : todayRates[secondaryCurrency];

    const equivalent = convertAmount(
        Number(amount),
        rate != null ? Number(rate) : todayRates[currency],
        secondaryRate,
    );

    if (equivalent === null) {
        return <span className={className}>{primary}</span>;
    }

    return (
        <span className={`inline-flex flex-col ${className}`}>
            <span>{primary}</span>
            <span className="text-[12px] font-normal text-muted-foreground">
                {formatMoney(
                    equivalent,
                    secondaryCurrency,
                    currencySymbol(secondaryCurrency, currencies),
                    decimals,
                )}
            </span>
        </span>
    );
}
