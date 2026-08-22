import { useCallback, useEffect, useRef, useState } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';

interface UseRemoteOptionProps {
    /** URL del endpoint de opciones, p. ej. `suppliers.lookup(companyId).url`. */
    url: string;
    /**
     * Lo que la pantalla ya sabe del valor elegido: su id y la etiqueta que
     * trajo el Resource del documento. Nulo al crear.
     */
    seed?: AjaxOption | null;
    /**
     * Pide al servidor el `meta` del valor sembrado. Solo hace falta cuando la
     * pantalla usa algo del valor además de su etiqueta.
     */
    hydrate?: boolean;
}

/**
 * Memoria del único valor que una pantalla ha elegido en un select remoto.
 *
 * `Select2Ajax` recibe la opción completa (`{ value, label, meta }`) y no un
 * id: no puede resolver la etiqueta de un id que nunca ha traído. Al editar,
 * esa opción de partida la arma la pantalla con lo que el Resource del
 * documento ya devuelve, y —si necesita su `meta`— se hidrata contra el mismo
 * endpoint por `ids`.
 */
export function useRemoteOption({
    url,
    seed = null,
    hydrate = false,
}: UseRemoteOptionProps) {
    const [option, setOption] = useState<AjaxOption | null>(seed);

    const seedId = seed?.value ?? '';
    const hydrated = useRef(false);

    useEffect(() => {
        if (!hydrate || hydrated.current || seedId === '') {
            return;
        }

        hydrated.current = true;

        const abort = new AbortController();

        void (async () => {
            try {
                const response = await fetch(
                    `${url}?ids=${seedId}&per_page=1`,
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: abort.signal,
                    },
                );

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const payload = (await response.json()) as {
                    data: AjaxOption[];
                };
                const [fresh] = payload.data;

                if (!fresh) {
                    return;
                }

                /* Si el usuario ya eligió otro, lo hidratado llegó tarde. */
                setOption((current) =>
                    current?.value === seedId ? fresh : current,
                );
            } catch {
                /*
                 * Sin hidratar, el select conserva la etiqueta que trajo el
                 * documento; lo que se pierde es el resto del `meta`, que el
                 * usuario recupera reeligiendo el valor.
                 */
                hydrated.current = false;
            }
        })();

        return () => abort.abort();
    }, [url, seedId, hydrate]);

    /**
     * La opción, solo mientras siga siendo la del id vigente del formulario:
     * al limpiarlo —un `reset()` tras guardar— el select queda vacío con él.
     */
    const optionOf = useCallback(
        (id: string): AjaxOption | null =>
            option && option.value === id ? option : null,
        [option],
    );

    return { url, optionOf, select: setOption };
}
