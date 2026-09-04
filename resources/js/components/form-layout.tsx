import * as React from 'react';

import { cn } from '@/lib/utils';

interface FormLayoutProps {
    onSubmit: (event: React.FormEvent) => void;
    className?: string;
    children: React.ReactNode;
}

/**
 * El esqueleto de cualquier formulario de creación o edición.
 *
 * Una sola columna a todo el ancho de la página: el resumen ya no compite por
 * el espacio de los campos, baja al pie con `FormSummary` y las acciones viven
 * en `FormActionBar`. El `min-w-0` es el que impide que un select o una tabla
 * de líneas estire el formulario más allá del contenedor.
 */
export function FormLayout({ onSubmit, className, children }: FormLayoutProps) {
    return (
        <form
            onSubmit={onSubmit}
            className={cn('flex min-w-0 flex-col gap-5', className)}
        >
            {children}
        </form>
    );
}

/**
 * La grilla de campos de una sección. Tres columnas en pantallas anchas: con
 * el formulario a ancho completo, dos dejaban cada campo en más de 400 px.
 *
 * Un campo que necesite toda la fila —una nota, una dirección— se marca con
 * `sm:col-span-2 xl:col-span-3` en su propio contenedor.
 */
export function FormFieldGrid({
    className,
    children,
}: {
    className?: string;
    children: React.ReactNode;
}) {
    return (
        <div
            className={cn(
                'grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3',
                className,
            )}
        >
            {children}
        </div>
    );
}
