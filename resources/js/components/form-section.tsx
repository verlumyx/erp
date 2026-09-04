import { ChevronDown } from 'lucide-react';
import * as React from 'react';

import { Card } from '@/components/ui/card';
import { cn } from '@/lib/utils';

interface FormSectionProps {
    /** Número de paso. Sin él el encabezado sale sin la insignia. */
    step?: number;
    title: string;
    sub?: string;
    /** Contenido alineado a la derecha del encabezado: un botón, un switch. */
    action?: React.ReactNode;
    /**
     * Con `onToggle` el encabezado pasa a ser un botón que pliega la sección.
     * Quien la usa sigue decidiendo si pinta el cuerpo; aquí solo se refleja el
     * estado en la flecha y en la línea inferior.
     */
    open?: boolean;
    onToggle?: () => void;
    className?: string;
    children: React.ReactNode;
}

/**
 * Una sección numerada del formulario: la tarjeta y su encabezado.
 *
 * El cuerpo se pasa tal cual, así que una sección de campos lo envuelve en
 * `FormFieldGrid` y una de líneas entrega su propio componente, que ya trae su
 * espaciado.
 */
export function FormSection({
    step,
    title,
    sub,
    action,
    open,
    onToggle,
    className,
    children,
}: FormSectionProps) {
    const head = (
        <>
            {step !== undefined && (
                <span className="grid size-[30px] shrink-0 place-items-center rounded-[9px] bg-primary-soft text-sm font-extrabold text-primary">
                    {step}
                </span>
            )}
            <div className="mr-auto min-w-0">
                <div className="text-base font-bold tracking-tight">
                    {title}
                </div>
                {sub && (
                    <div className="mt-0.5 text-[13px] text-muted-foreground">
                        {sub}
                    </div>
                )}
            </div>
        </>
    );

    return (
        <Card
            className={cn('gap-0 overflow-hidden rounded-2xl py-0', className)}
        >
            {onToggle ? (
                <button
                    type="button"
                    onClick={onToggle}
                    aria-expanded={open}
                    className={cn(
                        'flex w-full items-center gap-3 p-5 text-left transition-colors hover:bg-accent/40',
                        open && 'border-b',
                    )}
                >
                    {head}
                    <ChevronDown
                        className={cn(
                            'size-5 shrink-0 text-muted-foreground transition-transform',
                            open && 'rotate-180',
                        )}
                    />
                </button>
            ) : (
                <div className="flex items-center gap-3 border-b p-5">
                    {head}
                    {action}
                </div>
            )}
            {children}
        </Card>
    );
}
