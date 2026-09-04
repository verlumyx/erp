import { useEffect, useState } from 'react';

/**
 * El panel plegado de cada línea —ubicación, rechazo, trazabilidad— y la regla
 * de que un mensaje de error nunca se quede dentro de él sin que nadie lo vea.
 *
 * La validación de línea llega con la clave `lines.{índice}.{campo}` y buena
 * parte de esos campos vive en el panel. Con el panel cerrado —que es como
 * nace— el usuario recibía un formulario que no se guardaba y ni un solo
 * mensaje en pantalla, así que el panel de la línea que trae error se abre
 * solo. Se mira el índice de la línea y no el nombre del campo a propósito:
 * mover un campo al panel no puede volver a esconder su mensaje.
 *
 * Abrirlo es solo el arranque: el usuario sigue pudiendo cerrarlo a mano, que
 * es lo que se pierde si el panel se fuerza abierto mientras el error dure.
 */
export function useLineDetailPanels(
    lines: ReadonlyArray<{ id: string }>,
    errors: Record<string, string | undefined>,
) {
    const [open, setOpen] = useState<Record<string, boolean>>({});

    /**
     * Las líneas que fallaron, como una cadena: el efecto compara valores y no
     * la identidad del arreglo, que cambia en cada render.
     */
    const failing = lines
        .filter((_, index) =>
            Object.keys(errors).some((key) =>
                key.startsWith(`lines.${index}.`),
            ),
        )
        .map((line) => line.id)
        .join('|');

    useEffect(() => {
        if (failing === '') {
            return;
        }

        setOpen((current) => ({
            ...current,
            ...Object.fromEntries(
                failing.split('|').map((id) => [id, true] as const),
            ),
        }));
    }, [failing]);

    return {
        isOpen: (lineId: string): boolean => open[lineId] === true,
        toggle: (lineId: string): void =>
            setOpen((current) => ({ ...current, [lineId]: !current[lineId] })),
    };
}
