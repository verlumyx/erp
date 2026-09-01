import { useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import {
    useRemoteOptionSet,
    type RemoteOptionSeed,
} from '@/hooks/use-remote-option-set';
import { generateUUID } from '@/lib/utils';
import clients from '@/routes/clients';
import routes from '@/routes/routes';
import type {
    ClientAddressOption,
    Route,
    RouteFrequency,
    RouteOptions,
    RouteType,
    Weekday,
} from '../types/Route';

interface UseRouteFormProps {
    mode: 'create' | 'edit';
    options: RouteOptions;
    initialData?: Route;
    onSuccess?: () => void;
}

/** Una fila de la plantilla de clientes, tal como la edita la pantalla. */
export interface RouteClientRow {
    id: string;
    client_id: string;
    /** Vacía deja que la parada tome la dirección por defecto del cliente. */
    client_address_id: string;
}

interface RouteFormData {
    id: string;
    name: string;
    description: string;
    type: RouteType;
    warehouse_id: string;
    driver_id: string;
    salesperson_id: string;
    vehicle_plate: string;
    vehicle_capacity_weight: number;
    vehicle_capacity_volume: number;
    frequency: RouteFrequency;
    weekdays: Weekday[];
    zone: string;
    city: string;
    estimated_duration_minutes: number;
    estimated_distance_km: number;
    notes: string;
    clients: RouteClientRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function emptyRow(): RouteClientRow {
    return {
        id: generateUUID(),
        client_id: '',
        client_address_id: '',
    };
}

/**
 * Solo se editan los clientes activos: los inactivos se conservan en la base
 * por la política de no borrado, pero no vuelven al formulario.
 */
function clientRows(model?: Route): RouteClientRow[] {
    return (model?.clients ?? [])
        .filter((row) => row.status === 'active')
        .sort((a, b) => a.sequence - b.sequence)
        .map((row) => ({
            id: row.id,
            client_id: row.client_id,
            client_address_id: row.client_address_id ?? '',
        }));
}

export function useRouteForm({
    mode,
    options,
    initialData,
    onSuccess,
}: UseRouteFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, transform, processing, errors, reset } =
        useForm<RouteFormData>({
            id: initialData?.id ?? generateUUID(),
            name: initialData?.name ?? '',
            description: initialData?.description ?? '',
            type: initialData?.type ?? 'delivery',
            warehouse_id: initialData?.warehouse_id ?? '',
            driver_id: initialData?.driver_id ?? '',
            salesperson_id: initialData?.salesperson_id ?? '',
            vehicle_plate: initialData?.vehicle_plate ?? '',
            vehicle_capacity_weight: Number(
                initialData?.vehicle_capacity_weight ?? 0,
            ),
            vehicle_capacity_volume: Number(
                initialData?.vehicle_capacity_volume ?? 0,
            ),
            frequency: initialData?.frequency ?? 'weekly',
            weekdays: initialData?.weekdays ?? [],
            zone: initialData?.zone ?? '',
            city: initialData?.city ?? '',
            estimated_duration_minutes: Number(
                initialData?.estimated_duration_minutes ?? 0,
            ),
            estimated_distance_km: Number(
                initialData?.estimated_distance_km ?? 0,
            ),
            notes: initialData?.notes ?? '',
            clients: clientRows(initialData),
        });

    /**
     * El padrón de clientes no viaja en las props: la pantalla solo conoce los
     * que trae la ruta y los que el usuario va eligiendo. De la opción elegida
     * salen además sus direcciones, así que no hay una segunda ida al servidor
     * para poblar el select de dirección.
     */
    const clientSeed: RemoteOptionSeed[] = useMemo(() => {
        const seeds = new Map<string, RemoteOptionSeed>();

        (initialData?.clients ?? [])
            .filter((row) => row.status === 'active')
            .forEach((row) =>
                seeds.set(row.client_id, {
                    id: row.client_id,
                    label: row.client_code
                        ? `${row.client_code} — ${row.client_name}`
                        : row.client_name,
                }),
            );

        data.clients.forEach((row) => {
            if (row.client_id !== '' && !seeds.has(row.client_id)) {
                seeds.set(row.client_id, { id: row.client_id });
            }
        });

        return [...seeds.values()];
    }, [initialData, data.clients]);

    const clientOptions = useRemoteOptionSet({
        url: clients.lookup(companyId).url,
        seed: clientSeed,
    });

    /** Las direcciones que trajo la opción del cliente ya elegido. */
    const addressesOf = (clientId: string): ClientAddressOption[] => {
        const option = clientOptions.optionOf(clientId);
        const addresses = option?.meta?.addresses;

        return Array.isArray(addresses)
            ? (addresses as ClientAddressOption[])
            : [];
    };

    const addRow = () => setData('clients', [...data.clients, emptyRow()]);

    const removeRow = (index: number) =>
        setData(
            'clients',
            data.clients.filter((_, i) => i !== index),
        );

    const updateRow = <K extends keyof RouteClientRow>(
        index: number,
        field: K,
        value: RouteClientRow[K],
    ) =>
        setData(
            'clients',
            data.clients.map((row, i) =>
                i === index ? { ...row, [field]: value } : row,
            ),
        );

    /** Cambiar de cliente invalida la dirección: era de otro. */
    const setRowClient = (index: number, option: AjaxOption | null) => {
        if (option) {
            clientOptions.remember(option);
        }

        setData(
            'clients',
            data.clients.map((row, i) =>
                i === index
                    ? {
                          ...row,
                          client_id: option?.value ?? '',
                          client_address_id: '',
                      }
                    : row,
            ),
        );
    };

    /** El orden de visita es el orden de la lista: se mueve arrastrando poco. */
    const moveRow = (index: number, offset: number) => {
        const target = index + offset;

        if (target < 0 || target >= data.clients.length) {
            return;
        }

        const next = [...data.clients];
        [next[index], next[target]] = [next[target], next[index]];

        setData('clients', next);
    };

    const toggleWeekday = (day: Weekday) =>
        setData(
            'weekdays',
            data.weekdays.includes(day)
                ? data.weekdays.filter((current) => current !== day)
                : [...data.weekdays, day],
        );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        /** El orden de visita se manda explícito: la posición en la lista. */
        transform((current) => ({
            ...current,
            clients: current.clients.map((row, index) => ({
                ...row,
                sequence: index + 1,
            })),
        }));

        if (mode === 'create') {
            post(routes.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(routes.update({ company: companyId, id: initialData.id }).url, {
                onSuccess,
            });
        }
    };

    return {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        reset,
        mode,
        options,
        addRow,
        removeRow,
        updateRow,
        setRowClient,
        moveRow,
        toggleWeekday,
        clientOptions,
        addressesOf,
    };
}
