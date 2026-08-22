import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfiguration } from '@/hooks/use-configuration';
import { useTodayRates } from '@/hooks/use-today-rates';

interface ExchangeRateFieldProps {
    /**
     * Tasa que viaja en el formulario. Con la corrección permitida nace con la
     * del catálogo a la vista; vacía se la deja resolver al backend.
     */
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
 * La tasa sale del catálogo a la fecha del documento. Si la empresa permite
 * corregirla (`allows_rate_override`), el campo se muestra editable y con la
 * tasa cargada a la vista; si no, es una casilla de solo lectura y la resuelve
 * el backend. Un campo vacío también la deja en manos del backend.
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
    const hasRate = todayRates[currency] !== undefined;

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
                    placeholder="Automática"
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
                {!canOverride
                    ? `La resuelve el sistema con la tasa del catálogo a ${dateLabel}`
                    : hasRate
                      ? `Tasa del catálogo; corrígela si hace falta. Vacía la vuelve a resolver el sistema a ${dateLabel}`
                      : `No hay tasa cargada para ${currency}: escríbela o cárgala en el catálogo`}
            </span>

            {error && <p className="text-sm text-bad">{error}</p>}
        </div>
    );
}
