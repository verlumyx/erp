import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfiguration } from '@/hooks/use-configuration';
import { useTodayRates } from '@/hooks/use-today-rates';
import { formatAmount } from '@/lib/money';

interface ExchangeRateFieldProps {
    /** Corrección manual. Vacío —el caso normal— la resuelve el sistema. */
    value: string;
    onValueChange: (value: string) => void;
    /** Moneda del documento: de ella sale la tasa de hoy que se ofrece. */
    currency: string;
    /** Cómo nombra el documento su fecha: «la fecha del pedido». */
    dateLabel: string;
    error?: string;
    id?: string;
}

/**
 * Tasa de cambio de un documento.
 *
 * La tasa la resuelve el backend con el catálogo a la fecha del documento; el
 * campo solo existe para corregirla, y únicamente si la empresa lo permite
 * (`allows_rate_override`). Dejarlo vacío es lo normal.
 */
export function ExchangeRateField({
    value,
    onValueChange,
    currency,
    dateLabel,
    error,
    id = 'exchange_rate',
}: ExchangeRateFieldProps) {
    const configuration = useConfiguration();
    const todayRates = useTodayRates();

    const canOverride = configuration?.allows_rate_override === 'yes';
    const todayRate = todayRates[currency];

    return (
        <div className="flex flex-col gap-1.5">
            <Label
                htmlFor={canOverride ? id : undefined}
                className="text-[13px] font-semibold"
            >
                Tasa de cambio
            </Label>

            {canOverride ? (
                <Input
                    id={id}
                    value={value}
                    inputMode="decimal"
                    placeholder={
                        todayRate ? formatAmount(todayRate, 4) : 'Automática'
                    }
                    onChange={(event) =>
                        onValueChange(
                            event.target.value
                                .replace(',', '.')
                                .replace(/[^0-9.]/g, ''),
                        )
                    }
                    className={`h-[42px] rounded-[10px] ${error ? 'border-bad' : ''}`}
                />
            ) : (
                <div
                    className={`flex h-[42px] items-center rounded-[10px] border bg-muted/40 px-3 text-sm text-muted-foreground ${error ? 'border-bad' : 'border-input'}`}
                >
                    Automática
                </div>
            )}

            <span className="text-[12px] text-muted-foreground">
                {canOverride
                    ? `Vacía: la resuelve el sistema con la tasa del catálogo a ${dateLabel}`
                    : `La resuelve el sistema con la tasa del catálogo a ${dateLabel}`}
            </span>

            {error && <p className="text-sm text-bad">{error}</p>}
        </div>
    );
}
