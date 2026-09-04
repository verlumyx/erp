import { Check } from 'lucide-react';
import * as React from 'react';

import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { cn } from '@/lib/utils';

interface SummaryRowProps {
    label: string;
    /** El valor: un texto, un número o un `AmountDual`. */
    children: React.ReactNode;
    /** Separa la fila de las anteriores con una línea. */
    divider?: boolean;
    /** La fila de cierre —el total— va más grande y en negrita fuerte. */
    emphasis?: boolean;
    /** Para un valor que además se colorea, como un impacto positivo o negativo. */
    valueClassName?: string;
}

/** Una fila del resumen: etiqueta a la izquierda, valor a la derecha. */
export function SummaryRow({
    label,
    children,
    divider,
    emphasis,
    valueClassName,
}: SummaryRowProps) {
    return (
        <div
            className={cn(
                'flex items-center justify-between',
                divider && 'border-t pt-2.5',
                emphasis ? 'text-[15px]' : 'text-[13.5px]',
            )}
        >
            <span
                className={cn(
                    emphasis
                        ? 'font-semibold'
                        : 'font-medium text-muted-foreground',
                )}
            >
                {label}
            </span>
            <b
                className={cn(
                    'tabular-nums',
                    emphasis ? 'font-extrabold' : 'font-bold',
                    valueClassName,
                )}
            >
                {children}
            </b>
        </div>
    );
}

interface FormSummaryProps {
    title?: string;
    /** Las filas, normalmente `SummaryRow`, y cualquier aclaración al pie. */
    children: React.ReactNode;
}

/**
 * El resumen del formulario, al pie y alineado a la derecha.
 *
 * Antes era una columna lateral fija que se llevaba 340 px de los campos. El
 * dato que hay que vigilar mientras se captura —el total— no se pierde: viaja
 * al `headline` de `FormActionBar`, que sí queda a la vista todo el tiempo.
 */
export function FormSummary({ title = 'Resumen', children }: FormSummaryProps) {
    return (
        <Card className="ml-auto w-full max-w-md gap-3.5 rounded-2xl p-5">
            <div className="text-base font-bold tracking-tight">{title}</div>
            <div className="flex flex-col gap-2.5">{children}</div>
        </Card>
    );
}

interface FormActionBarProps {
    processing: boolean;
    /** "Crear factura", "Guardar cambios"… */
    submitLabel: string;
    /** La cifra que conviene tener siempre delante: el total, el porcentaje. */
    headline?: React.ReactNode;
    headlineLabel?: string;
    /** Por defecto vuelve a la pantalla anterior. */
    onCancel?: () => void;
}

/**
 * La barra de acciones, pegada al fondo de la ventana mientras se scrollea.
 *
 * Sustituye a los botones que vivían dentro del resumen lateral y que por
 * debajo de 1280 px quedaban enterrados al final de un scroll largo.
 */
export function FormActionBar({
    processing,
    submitLabel,
    headline,
    headlineLabel,
    onCancel,
}: FormActionBarProps) {
    return (
        <div className="sticky bottom-5 z-20 flex flex-wrap items-center gap-3 rounded-2xl border bg-card/90 px-5 py-3.5 shadow-lg backdrop-blur-md">
            {headline !== undefined && (
                <div className="mr-auto flex items-center gap-2.5">
                    {headlineLabel && (
                        <span className="hidden text-[13.5px] font-medium text-muted-foreground sm:inline">
                            {headlineLabel}
                        </span>
                    )}
                    <b className="text-[17px] font-extrabold tabular-nums">
                        {headline}
                    </b>
                </div>
            )}
            <div className="ml-auto flex items-center gap-3">
                <Button
                    type="button"
                    variant="outline"
                    className="h-10 justify-center rounded-[11px] bg-card px-5 font-semibold"
                    onClick={onCancel ?? (() => window.history.back())}
                    disabled={processing}
                >
                    Cancelar
                </Button>
                <Button
                    type="submit"
                    disabled={processing}
                    className="h-10 justify-center rounded-[11px] px-5 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                >
                    <Check />
                    {processing ? 'Guardando…' : submitLabel}
                </Button>
            </div>
        </div>
    );
}
