import { router, usePage } from '@inertiajs/react';
import { Edit, Eye, MoreHorizontal, Plus, Power, Search } from 'lucide-react';
import { useState } from 'react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import warehouseLocations from '@/routes/warehouse-locations';
import {
    LOCATION_TYPE_LABELS,
    type WarehouseLocation,
    type WarehouseLocationFilters,
    type WarehouseLocationMeta,
    type WarehouseOption,
} from '../types/WarehouseLocation';

interface WarehouseLocationListProps {
    locations: WarehouseLocation[];
    warehouses: WarehouseOption[];
    meta: WarehouseLocationMeta;
    filters: WarehouseLocationFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const STATUS_OPTIONS: OptionType[] = [
    { value: 'todos', label: 'Todos' },
    { value: 'active', label: 'Activas' },
    { value: 'inactive', label: 'Inactivas' },
];

export function WarehouseLocationList({
    locations,
    warehouses,
    meta,
    filters: initialFilters,
}: WarehouseLocationListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] =
        useState<WarehouseLocationFilters>(initialFilters);

    const warehouseOptions: OptionType[] = [
        { value: 'todas', label: 'Todas' },
        ...warehouses.map((warehouse) => ({
            value: warehouse.id,
            label: warehouse.name,
        })),
    ];

    const applyFilters = (next: WarehouseLocationFilters) => {
        setFilters(next);
        router.get(
            warehouseLocations.index(companyId).url,
            next as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(warehouseLocations.index(companyId).url);
    };

    const handleToggleStatus = (location: WarehouseLocation) => {
        router.put(
            warehouseLocations.updateStatus({
                company: companyId,
                id: location.id,
            }).url,
            {
                status: location.status === 'active' ? 'inactive' : 'active',
            },
        );
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Ubicaciones
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} ubicaci{meta.total !== 1 ? 'ones' : 'ón'}
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() =>
                        router.visit(warehouseLocations.create(companyId).url)
                    }
                >
                    <Plus />
                    Nueva ubicación
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {(
                        [
                            ['name', 'Nombre'],
                            ['location_code', 'Código físico'],
                        ] as Array<[keyof WarehouseLocationFilters, string]>
                    ).map(([key, label]) => (
                        <div key={key} className="space-y-2">
                            <Label htmlFor={`filter-${key}`}>{label}</Label>
                            <Input
                                id={`filter-${key}`}
                                type="text"
                                placeholder={`Buscar por ${label.toLowerCase()}...`}
                                value={
                                    (filters[key] as string | undefined) ?? ''
                                }
                                onChange={(e) =>
                                    setFilters({
                                        ...filters,
                                        [key]: e.target.value,
                                    })
                                }
                                onKeyDown={(e) =>
                                    e.key === 'Enter' && applyFilters(filters)
                                }
                            />
                        </div>
                    ))}
                    <div className="space-y-2">
                        <Label htmlFor="filter-warehouse">Bodega</Label>
                        <Select2
                            inputId="filter-warehouse"
                            options={warehouseOptions}
                            value={
                                warehouseOptions.find(
                                    (option) =>
                                        option.value ===
                                        (filters.warehouse_id ?? 'todas'),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    warehouse_id:
                                        !option || option.value === 'todas'
                                            ? undefined
                                            : option.value,
                                })
                            }
                            placeholder="Bodega"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-status">Estado</Label>
                        <Select2
                            inputId="filter-status"
                            options={STATUS_OPTIONS}
                            value={
                                STATUS_OPTIONS.find(
                                    (option) =>
                                        option.value ===
                                        (filters.status ?? 'todos'),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    status:
                                        !option || option.value === 'todos'
                                            ? undefined
                                            : option.value,
                                })
                            }
                            placeholder="Estado"
                            isSearchable={false}
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.2fr_1.2fr_0.9fr_1fr]">
                    {[
                        'Código',
                        'Ubicación',
                        'Bodega',
                        'Estado',
                        'Acciones',
                    ].map((h, i) => (
                        <div
                            key={h}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                i === 4 ? 'text-right' : ''
                            }`}
                        >
                            {h}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {locations.map((location) => {
                        const estadoUbicacion =
                            location.status === 'inactive'
                                ? 'inactivo'
                                : 'activo';
                        return (
                            <div
                                key={location.id}
                                className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.2fr_1.2fr_0.9fr_1fr] lg:py-0"
                                onClick={() =>
                                    router.visit(
                                        warehouseLocations.show({
                                            company: companyId,
                                            id: location.id,
                                        }).url,
                                    )
                                }
                            >
                                <div className="hidden lg:block">
                                    <span className="font-semibold text-muted-foreground tabular-nums">
                                        {location.code}
                                    </span>
                                </div>
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {location.location_code} ·{' '}
                                        {location.name}
                                        {location.is_default === 'yes' && (
                                            <span className="ml-2 rounded-full bg-primary-soft px-2 py-0.5 text-[11px] font-bold text-primary">
                                                Por defecto
                                            </span>
                                        )}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {LOCATION_TYPE_LABELS[location.type]}
                                        {location.parent_name
                                            ? ` · dentro de ${location.parent_name}`
                                            : ''}
                                    </span>
                                </div>
                                <div className="hidden lg:block">
                                    <span className="truncate text-[13px] font-semibold text-muted-foreground">
                                        {location.warehouse_name ?? '—'}
                                    </span>
                                </div>
                                <div className="hidden lg:block">
                                    <StatusPill kind={estadoUbicacion} />
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
                                                        warehouseLocations.show(
                                                            {
                                                                company:
                                                                    companyId,
                                                                id: location.id,
                                                            },
                                                        ).url,
                                                    )
                                                }
                                            >
                                                <Eye className="mr-2 h-4 w-4" />
                                                Ver
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    router.visit(
                                                        warehouseLocations.edit(
                                                            {
                                                                company:
                                                                    companyId,
                                                                id: location.id,
                                                            },
                                                        ).url,
                                                    )
                                                }
                                            >
                                                <Edit className="mr-2 h-4 w-4" />
                                                Editar
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    handleToggleStatus(location)
                                                }
                                            >
                                                <Power className="mr-2 h-4 w-4" />
                                                {location.status === 'active'
                                                    ? 'Inactivar'
                                                    : 'Activar'}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        );
                    })}
                    {locations.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Sin resultados para tu búsqueda.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {locations.length} de {meta.total} ubicaci
                    {meta.total !== 1 ? 'ones' : 'ón'}
                </div>
            </Card>
        </div>
    );
}
