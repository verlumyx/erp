import { router, usePage } from '@inertiajs/react';
import { Edit, Eye, MoreHorizontal, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import routeRoutes from '@/routes/routes';
import {
    FREQUENCY_LABELS,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    TYPE_LABELS,
    weekdayLabels,
    type Route,
    type RouteFilters,
    type RouteMeta,
    type RouteOptions,
} from '../types/Route';

interface RouteListProps {
    routes: Route[];
    meta: RouteMeta;
    filters: RouteFilters;
    options: RouteOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const ALL = 'todos';

const STATUS_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todos' },
    ...Object.entries(STATUS_LABELS).map(([value, label]) => ({
        value,
        label,
    })),
];

const TYPE_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todos' },
    ...Object.entries(TYPE_LABELS).map(([value, label]) => ({ value, label })),
];

const FREQUENCY_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todas' },
    ...Object.entries(FREQUENCY_LABELS).map(([value, label]) => ({
        value,
        label,
    })),
];

export function RouteList({
    routes: rows,
    meta,
    filters: initialFilters,
    options,
}: RouteListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<RouteFilters>(initialFilters);

    const driverOptions: OptionType[] = [
        { value: ALL, label: 'Todos' },
        ...options.users.map((user) => ({ value: user.id, label: user.name })),
    ];

    const applyFilters = (next: RouteFilters) => {
        setFilters(next);
        router.get(
            routeRoutes.index(companyId).url,
            next as Record<string, string>,
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(routeRoutes.index(companyId).url);
    };

    /** Un select de filtro: `todos` significa «sin filtrar». */
    const pickFilter = (
        list: OptionType[],
        value: string | undefined,
        apply: (next: string | undefined) => void,
    ) => ({
        options: list,
        value: list.find((option) => option.value === (value ?? ALL)) ?? null,
        onChange: (option: OptionType | null) =>
            apply(!option || option.value === ALL ? undefined : option.value),
    });

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Rutas
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} ruta{meta.total !== 1 ? 's' : ''}
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() =>
                        router.visit(routeRoutes.create(companyId).url)
                    }
                >
                    <Plus />
                    Nueva ruta
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div className="space-y-2">
                        <Label htmlFor="filter-name">Nombre o código</Label>
                        <Input
                            id="filter-name"
                            type="text"
                            placeholder="Buscar por nombre..."
                            value={filters.name ?? ''}
                            onChange={(e) =>
                                setFilters({ ...filters, name: e.target.value })
                            }
                            onKeyDown={(e) =>
                                e.key === 'Enter' && applyFilters(filters)
                            }
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-zone">Zona</Label>
                        <Input
                            id="filter-zone"
                            type="text"
                            placeholder="Buscar por zona..."
                            value={filters.zone ?? ''}
                            onChange={(e) =>
                                setFilters({ ...filters, zone: e.target.value })
                            }
                            onKeyDown={(e) =>
                                e.key === 'Enter' && applyFilters(filters)
                            }
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-type">Tipo</Label>
                        <Select2
                            inputId="filter-type"
                            {...pickFilter(TYPE_OPTIONS, filters.type, (type) =>
                                applyFilters({ ...filters, type }),
                            )}
                            placeholder="Todos"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-frequency">Frecuencia</Label>
                        <Select2
                            inputId="filter-frequency"
                            {...pickFilter(
                                FREQUENCY_OPTIONS,
                                filters.frequency,
                                (frequency) =>
                                    applyFilters({ ...filters, frequency }),
                            )}
                            placeholder="Todas"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-driver">Conductor</Label>
                        <Select2
                            inputId="filter-driver"
                            {...pickFilter(
                                driverOptions,
                                filters.driver_id,
                                (driver_id) =>
                                    applyFilters({ ...filters, driver_id }),
                            )}
                            placeholder="Todos"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-status">Estado</Label>
                        <Select2
                            inputId="filter-status"
                            {...pickFilter(
                                STATUS_OPTIONS,
                                filters.status,
                                (status) =>
                                    applyFilters({ ...filters, status }),
                            )}
                            placeholder="Todos"
                        />
                    </div>
                </div>
                <div className="mt-4 flex justify-end gap-2">
                    <Button
                        onClick={() => applyFilters(filters)}
                        variant="default"
                    >
                        <Search className="mr-2 h-4 w-4" />
                        Buscar
                    </Button>
                    <Button onClick={handleClear} variant="outline">
                        Limpiar
                    </Button>
                </div>
            </div>

            <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.4fr_1.2fr_1fr_0.9fr_0.8fr]">
                    {[
                        'Código',
                        'Ruta',
                        'Cuándo',
                        'Equipo',
                        'Estado',
                        'Acciones',
                    ].map((header, index) => (
                        <div
                            key={header}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                index === 5 ? 'text-right' : ''
                            }`}
                        >
                            {header}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {rows.map((row) => (
                        <div
                            key={row.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.4fr_1.2fr_1fr_0.9fr_0.8fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    routeRoutes.show({
                                        company: companyId,
                                        id: row.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {row.code}
                                </span>
                            </div>
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {row.name}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {TYPE_LABELS[row.type]}
                                    {row.zone ? ` · ${row.zone}` : ''}
                                    {row.clients_count !== undefined
                                        ? ` · ${row.clients_count} cliente${row.clients_count !== 1 ? 's' : ''}`
                                        : ''}
                                </span>
                            </div>
                            <div className="hidden min-w-0 flex-col lg:flex">
                                <span className="truncate text-[13.5px] font-medium">
                                    {FREQUENCY_LABELS[row.frequency]}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {weekdayLabels(row.weekdays) || '—'}
                                </span>
                            </div>
                            <div className="hidden min-w-0 truncate text-[13.5px] text-muted-foreground lg:block">
                                {row.driver_name ?? '—'}
                                {row.vehicle_plate
                                    ? ` · ${row.vehicle_plate}`
                                    : ''}
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill kind={STATUS_PILL_KIND[row.status]}>
                                    {STATUS_LABELS[row.status]}
                                </StatusPill>
                            </div>
                            <div
                                className="flex items-center justify-end gap-2"
                                onClick={(e) => e.stopPropagation()}
                            >
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            className="rounded-[10px] bg-card"
                                            aria-label="Opciones"
                                        >
                                            <MoreHorizontal className="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            onClick={() =>
                                                router.visit(
                                                    routeRoutes.show({
                                                        company: companyId,
                                                        id: row.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            onClick={() =>
                                                router.visit(
                                                    routeRoutes.edit({
                                                        company: companyId,
                                                        id: row.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Edit className="mr-2 h-4 w-4" />
                                            Editar
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    ))}
                    {rows.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Sin resultados para tu búsqueda.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {rows.length} de {meta.total} ruta
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
