import { Pagination } from '@/components/pagination';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Edit,
    Eye,
    MoreHorizontal,
    Plus,
    Power,
    Search,
    Wrench,
} from 'lucide-react';
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
import vehicles from '@/routes/vehicles';
import {
    STATUS_LABELS,
    STATUS_PILL_KIND,
    VEHICLE_TYPE_LABELS,
    type Vehicle,
    type VehicleFilters,
    type VehicleMeta,
    type VehicleStatus,
} from '../types/Vehicle';

interface VehicleListProps {
    vehicles: Vehicle[];
    meta: VehicleMeta;
    filters: VehicleFilters;
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
    ...Object.entries(VEHICLE_TYPE_LABELS).map(([value, label]) => ({
        value,
        label,
    })),
];

export function VehicleList({
    vehicles: rows,
    meta,
    filters: initialFilters,
}: VehicleListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<VehicleFilters>(initialFilters);

    const applyFilters = (next: VehicleFilters) => {
        setFilters(next);
        router.get(
            vehicles.index(companyId).url,
            next as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(vehicles.index(companyId).url);
    };

    const handleToggleStatus = (vehicle: Vehicle, status: VehicleStatus) => {
        router.put(
            vehicles.updateStatus({ company: companyId, id: vehicle.id }).url,
            { status },
            { preserveScroll: true },
        );
    };

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
                        Camiones
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} cami{meta.total !== 1 ? 'ones' : 'ón'}
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() =>
                        router.visit(vehicles.create(companyId).url)
                    }
                >
                    <Plus />
                    Nuevo camión
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div className="space-y-2">
                        <Label htmlFor="filter-q">Buscar por placa, nombre o marca</Label>
                        <Input
                            id="filter-q"
                            type="text"
                            placeholder="Ej: A12BC3D, Cargo, Chevrolet..."
                            value={filters.q ?? ''}
                            onChange={(e) =>
                                setFilters({ ...filters, q: e.target.value })
                            }
                            onKeyDown={(e) =>
                                e.key === 'Enter' &&
                                applyFilters({ ...filters, offset: 0 })
                            }
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-type">Tipo de vehículo</Label>
                        <Select2
                            inputId="filter-type"
                            {...pickFilter(
                                TYPE_OPTIONS,
                                filters.vehicle_type,
                                (vehicle_type) =>
                                    applyFilters({
                                        ...filters,
                                        vehicle_type,
                                        offset: 0,
                                    }),
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
                                    applyFilters({
                                        ...filters,
                                        status: status as VehicleStatus | undefined,
                                        offset: 0,
                                    }),
                            )}
                            placeholder="Todos"
                        />
                    </div>
                </div>
                <div className="mt-4 flex justify-end gap-2">
                    <Button
                        onClick={() => applyFilters({ ...filters, offset: 0 })}
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[1.1fr_1.8fr_1.4fr_1.4fr_0.9fr_0.9fr_0.9fr_0.6fr]">
                    {[
                        'Placa / Código',
                        'Descripción',
                        'Marca y Modelo',
                        'Tipo',
                        'Cap. Peso',
                        'Cap. Volumen',
                        'Estado',
                        'Acciones',
                    ].map((header, index) => (
                        <div
                            key={header}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                index === 4 || index === 5
                                    ? 'text-right'
                                    : index === 6
                                      ? 'text-center'
                                      : index === 7
                                        ? 'text-right'
                                        : ''
                            }`}
                        >
                            {header}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {rows.map((vehicle) => (
                        <div
                            key={vehicle.id}
                            className="grid min-h-[66px] grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted/50 lg:grid-cols-[1.1fr_1.8fr_1.4fr_1.4fr_0.9fr_0.9fr_0.9fr_0.6fr] lg:py-0"
                        >
                            <div className="flex flex-col">
                                <Link
                                    href={
                                        vehicles.show({
                                            company: companyId,
                                            id: vehicle.id,
                                        }).url
                                    }
                                    className="font-mono font-bold text-primary hover:underline"
                                >
                                    {vehicle.plate}
                                </Link>
                                <Link
                                    href={
                                        vehicles.show({
                                            company: companyId,
                                            id: vehicle.id,
                                        }).url
                                    }
                                    className="text-[12px] text-muted-foreground hover:underline"
                                >
                                    {vehicle.code}
                                </Link>
                            </div>

                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold text-foreground">
                                    {vehicle.name}
                                </span>
                            </div>

                            <div className="hidden min-w-0 truncate text-[13.5px] text-muted-foreground lg:block">
                                {[vehicle.brand, vehicle.model, vehicle.year]
                                    .filter(Boolean)
                                    .join(' · ') || '—'}
                            </div>

                            <div className="hidden min-w-0 truncate text-[13.5px] text-muted-foreground lg:block">
                                {vehicle.vehicle_type_label ||
                                    VEHICLE_TYPE_LABELS[vehicle.vehicle_type] ||
                                    vehicle.vehicle_type}
                            </div>

                            <div className="hidden text-right text-[13.5px] font-medium tabular-nums lg:block">
                                {vehicle.capacity_weight > 0
                                    ? `${vehicle.capacity_weight.toLocaleString()} kg`
                                    : '—'}
                            </div>

                            <div className="hidden text-right text-[13.5px] font-medium tabular-nums lg:block">
                                {vehicle.capacity_volume > 0
                                    ? `${vehicle.capacity_volume.toLocaleString()} m³`
                                    : '—'}
                            </div>

                            <div className="hidden text-center lg:block">
                                <StatusPill
                                    kind={STATUS_PILL_KIND[vehicle.status]}
                                >
                                    {STATUS_LABELS[vehicle.status]}
                                </StatusPill>
                            </div>

                            <div className="flex items-center justify-end gap-2">
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-7 rounded-md text-muted-foreground hover:text-foreground"
                                            aria-label="Opciones"
                                        >
                                            <MoreHorizontal className="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align="end"
                                        className="w-48"
                                    >
                                        <DropdownMenuItem asChild>
                                            <Link
                                                href={
                                                    vehicles.show({
                                                        company: companyId,
                                                        id: vehicle.id,
                                                    }).url
                                                }
                                                className="flex items-center gap-2 text-xs"
                                            >
                                                <Eye className="size-3.5 text-muted-foreground" />
                                                Ver detalle
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem asChild>
                                            <Link
                                                href={
                                                    vehicles.edit({
                                                        company: companyId,
                                                        id: vehicle.id,
                                                    }).url
                                                }
                                                className="flex items-center gap-2 text-xs"
                                            >
                                                <Edit className="size-3.5 text-muted-foreground" />
                                                Editar
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        {vehicle.status !== 'active' && (
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    handleToggleStatus(
                                                        vehicle,
                                                        'active',
                                                    )
                                                }
                                                className="flex items-center gap-2 text-xs text-primary"
                                            >
                                                <Power className="size-3.5" />
                                                Poner operativo
                                            </DropdownMenuItem>
                                        )}
                                        {vehicle.status !== 'maintenance' && (
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    handleToggleStatus(
                                                        vehicle,
                                                        'maintenance',
                                                    )
                                                }
                                                className="flex items-center gap-2 text-xs text-warning"
                                            >
                                                <Wrench className="size-3.5" />
                                                Poner en taller
                                            </DropdownMenuItem>
                                        )}
                                        {vehicle.status !== 'inactive' && (
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    handleToggleStatus(
                                                        vehicle,
                                                        'inactive',
                                                    )
                                                }
                                                className="flex items-center gap-2 text-xs text-bad"
                                            >
                                                <Power className="size-3.5" />
                                                Desactivar
                                            </DropdownMenuItem>
                                        )}
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
                <Pagination
                    meta={meta}
                    onPageChange={(newOffset) =>
                        applyFilters({ ...filters, offset: newOffset })
                    }
                    label="camión"
                    labelPlural="camiones"
                />
            </Card>
        </div>
    );
}
