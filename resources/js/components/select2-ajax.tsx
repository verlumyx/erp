import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import ReactSelect, { type GroupBase } from 'react-select';
import {
    createSelect2Styles,
    Select2DropdownIndicator,
    type OptionType,
    type Select2Size,
} from '@/components/ui/select2';
import { cn } from '@/lib/utils';

/**
 * Opción devuelta por un endpoint de opciones.
 *
 * `meta` viaja tal cual desde el `<Módulo>OptionResource` del backend: es lo que
 * permite que al elegir un artículo el formulario ya tenga su precio, su
 * impuesto o su unidad base sin una segunda petición.
 */
export interface AjaxOption extends OptionType {
    meta?: Record<string, unknown>;
}

/** Contrato de respuesta del endpoint `GET {módulo}/lookup`. */
interface OptionsResponse {
    data: AjaxOption[];
    has_more: boolean;
}

type QueryParams = Record<string, string | number | boolean | null | undefined>;

interface Select2AjaxProps {
    /** URL del endpoint de opciones, p. ej. `items.lookup(companyId).url`. */
    url: string;
    /** Filtros fijos que acotan el catálogo (`{ is_sellable: 'yes' }`). */
    params?: QueryParams;
    value: AjaxOption | null;
    onChange: (option: AjaxOption | null) => void;
    /** Caracteres mínimos antes de consultar. `0` carga al abrir el menú. */
    minSearchLength?: number;
    perPage?: number;
    debounceMs?: number;
    error?: boolean;
    size?: Select2Size;
    placeholder?: string;
    isClearable?: boolean;
    isDisabled?: boolean;
    /**
     * Etiqueta a mostrar, si el módulo la arma distinto del servidor (p. ej.
     * por sku en ventas y por código en compras). Aplica al menú y al valor
     * seleccionado por igual.
     */
    formatLabel?: (option: AjaxOption) => string;
    /** Id del input interno; úsalo para enlazar el `<Label htmlFor>`. */
    inputId?: string;
    className?: string;
}

const buildUrl = (url: string, params: QueryParams): string => {
    const search = new URLSearchParams();

    for (const [key, value] of Object.entries(params)) {
        if (value === null || value === undefined || value === '') {
            continue;
        }

        search.set(key, String(value));
    }

    return `${url}${url.includes('?') ? '&' : '?'}${search.toString()}`;
};

/**
 * Select con búsqueda contra el servidor.
 *
 * Se usa en lugar de `Select2` cuando el catálogo es demasiado grande para
 * viajar entero en las props de Inertia (artículos, clientes, proveedores): el
 * servidor filtra y pagina, el componente solo pide páginas.
 *
 * A diferencia de `Select2`, el valor es la opción completa (`{ value, label }`)
 * y no solo el id: el componente no puede resolver la etiqueta de un id que
 * nunca ha traído. En modo edición, esa opción inicial la arma la pantalla con
 * lo que ya devuelve el Resource del documento.
 */
export function Select2Ajax({
    url,
    params,
    value,
    onChange,
    minSearchLength = 0,
    perPage = 20,
    debounceMs = 300,
    error = false,
    size = 'sm',
    placeholder = 'Busca una opción...',
    isClearable = false,
    isDisabled = false,
    formatLabel,
    inputId,
    className,
}: Select2AjaxProps) {
    const [options, setOptions] = useState<AjaxOption[]>([]);
    const [inputValue, setInputValue] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [hasMore, setHasMore] = useState(false);
    const [failed, setFailed] = useState(false);

    const page = useRef(1);
    const requestId = useRef(0);
    const controller = useRef<AbortController | null>(null);
    const debounce = useRef<ReturnType<typeof setTimeout> | null>(null);

    /** Los filtros fijos llegan como literal: sin serializar reinician el efecto en cada render. */
    const paramsKey = JSON.stringify(params ?? {});

    const styles = useMemo(
        () => createSelect2Styles<AjaxOption>({ size, error }),
        [size, error],
    );

    const load = useCallback(
        async (term: string, nextPage: number) => {
            controller.current?.abort();

            const current = ++requestId.current;
            const abort = new AbortController();
            controller.current = abort;

            setIsLoading(true);
            setFailed(false);

            try {
                const response = await fetch(
                    buildUrl(url, {
                        ...(JSON.parse(paramsKey) as QueryParams),
                        q: term,
                        page: nextPage,
                        per_page: perPage,
                    }),
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

                const payload = (await response.json()) as OptionsResponse;

                /* Una respuesta que llegó tarde no puede pisar a la vigente. */
                if (current !== requestId.current) {
                    return;
                }

                page.current = nextPage;
                setHasMore(payload.has_more);
                setOptions((previous) =>
                    nextPage === 1
                        ? payload.data
                        : [...previous, ...payload.data],
                );
            } catch (exception) {
                if (
                    exception instanceof DOMException &&
                    exception.name === 'AbortError'
                ) {
                    return;
                }

                if (current !== requestId.current) {
                    return;
                }

                setFailed(true);
                setHasMore(false);
                setOptions([]);
            } finally {
                if (current === requestId.current) {
                    setIsLoading(false);
                }
            }
        },
        [url, paramsKey, perPage],
    );

    /** Los filtros fijos acotan el catálogo: al cambiar, lo ya traído deja de valer. */
    useEffect(() => {
        setOptions([]);
        setHasMore(false);
        page.current = 1;
    }, [url, paramsKey]);

    useEffect(() => {
        return () => {
            controller.current?.abort();

            if (debounce.current) {
                clearTimeout(debounce.current);
            }
        };
    }, []);

    /** Vuelve al estado de partida: sin término, sin páginas traídas. */
    const reset = useCallback(() => {
        if (debounce.current) {
            clearTimeout(debounce.current);
        }

        controller.current?.abort();
        requestId.current++;
        page.current = 1;
        setInputValue('');
        setOptions([]);
        setHasMore(false);
        setIsLoading(false);
    }, []);

    const search = useCallback(
        (term: string) => {
            if (debounce.current) {
                clearTimeout(debounce.current);
            }

            if (term.length < minSearchLength) {
                controller.current?.abort();
                requestId.current++;
                setOptions([]);
                setHasMore(false);
                setIsLoading(false);

                return;
            }

            debounce.current = setTimeout(() => void load(term, 1), debounceMs);
        },
        [load, minSearchLength, debounceMs],
    );

    const noOptionsMessage = () => {
        if (failed) {
            return 'No se pudieron cargar las opciones';
        }

        if (inputValue.length < minSearchLength) {
            return `Escribe al menos ${minSearchLength} caracteres`;
        }

        return 'No hay opciones disponibles';
    };

    return (
        <ReactSelect<AjaxOption, false, GroupBase<AjaxOption>>
            inputId={inputId}
            styles={styles}
            components={{ DropdownIndicator: Select2DropdownIndicator }}
            className={cn('react-select-container', className)}
            classNamePrefix="react-select"
            menuPortalTarget={
                typeof document !== 'undefined' ? document.body : undefined
            }
            menuPosition="fixed"
            options={options}
            value={value}
            getOptionLabel={
                formatLabel ? (option) => formatLabel(option) : undefined
            }
            onChange={(option) => onChange(option)}
            inputValue={inputValue}
            onInputChange={(term, action) => {
                if (action.action === 'input-change') {
                    setInputValue(term);
                    search(term);

                    return;
                }

                /*
                 * El input es controlado: si no lo vaciamos al elegir o al
                 * cerrar el menú, el término tapa la etiqueta seleccionada.
                 */
                if (
                    action.action === 'set-value' ||
                    action.action === 'menu-close' ||
                    action.action === 'input-blur'
                ) {
                    reset();
                }
            }}
            onMenuOpen={() => {
                if (
                    options.length === 0 &&
                    inputValue.length >= minSearchLength
                ) {
                    void load(inputValue, 1);
                }
            }}
            onMenuScrollToBottom={() => {
                if (hasMore && !isLoading) {
                    void load(inputValue, page.current + 1);
                }
            }}
            /* El servidor ya filtró: filtrar otra vez en el cliente esconde resultados. */
            filterOption={null}
            isLoading={isLoading}
            isClearable={isClearable}
            isDisabled={isDisabled}
            placeholder={placeholder}
            loadingMessage={() => 'Buscando...'}
            noOptionsMessage={noOptionsMessage}
        />
    );
}
