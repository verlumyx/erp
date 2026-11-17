import { Pagination } from '@/components/pagination';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Edit,
    Eye,
    MoreHorizontal,
    Plus,
    Search,
    Truck,
    Wrench,
    Power,
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

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-extrabold tracking-tight">
                        Camiones y Flota
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Gestiona los camiones de reparto, placas y capacidades físicas de carga
                    </p>
                </div>
                <Link href={vehicles.create(companyId).url}>
                    <Button className="h-10 rounded-[11px] px-4 font-semibold">
                        <Plus className="mr-1.5 size-4" />
                        Nuevo camión
                    </Button>
                </Link>
            </div>

            <Card className="gap-3 rounded-2xl p-4">
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
                    <div className="flex flex-col gap-1.5 md:col-span-2">
                        <Label
                            htmlFor="filter-q"
                            className="text-[12px] font-semibold text-muted-foreground"
                        >
                            Buscar por placa, nombre o marca
                        </Label>
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="filter-q"
                                value={filters.q ?? ''}
                                onChange={(e) =>
                                    setFilters({ ...filters, q: e.target.value })
                                }
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        applyFilters({
                                            ...filters,
                                            offset: 0,
                                        });
                                    }
                                }}
                                placeholder="Ej: A12BC3D, Cargo, Chevrolet..."
                                className="h-[40px] rounded-[10px] pl-9"
                            />
                        </div>
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[12px] font-semibold text-muted-foreground">
                            Tipo de vehículo
                        </Label>
                        <Select2
                            inputId="filter-type"
                            options={TYPE_OPTIONS}
                            value={
                                TYPE_OPTIONS.find(
                                    (opt) =>
                                        opt.value ===
                                        (filters.vehicle_type || ALL),
                                ) ?? TYPE_OPTIONS[0]
                            }
                            onChange={(opt) => {
                                const next = {
                                    ...filters,
                                    vehicle_type:
                                        !opt || opt.value === ALL
                                            ? undefined
                                            : opt.value,
                                    offset: 0,
                                };
                                applyFilters(next);
                            }}
                            size="sm"
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[12px] font-semibold text-muted-foreground">
                            Estado
                        </Label>
                        <Select2
                            inputId="filter-status"
                            options={STATUS_OPTIONS}
                            value={
                                STATUS_OPTIONS.find(
                                    (opt) =>
                                        opt.value === (filters.status || ALL),
                                ) ?? STATUS_OPTIONS[0]
                            }
                            onChange={(opt) => {
                                const next = {
                                    ...filters,
                                    status:
                                        !opt || opt.value === ALL
                                            ? undefined
                                            : opt.value,
                                    offset: 0,
                                };
                                applyFilters(next);
                            }}
                            size="sm"
                        />
                    </div>
                </div>

                <div className="flex justify-end gap-2 pt-1 border-t">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={handleClear}
                        className="h-8 rounded-lg text-xs"
                    >
                        Limpiar filtros
                    </Button>
                    <Button
                        size="sm"
                        onClick={() =>
                            applyFilters({ ...filters, offset: 0 })
                        }
                        className="h-8 rounded-lg px-3 text-xs font-semibold"
                    >
                        Filtrar
                    </Button>
                </div>
            </Card>

            <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-muted/40 text-[12px] font-bold text-muted-foreground">
                            <tr>
                                <th className="px-5 py-3.5">Placa / Código</th>
                                <th className="px-5 py-3.5">Descripción</th>
                                <th className="px-5 py-3.5">Marca y Modelo</th>
                                <th className="px-5 py-3.5">Tipo</th>
                                <th className="px-5 py-3.5 text-right">Cap. Peso</th>
                                <th className="px-5 py-3.5 text-right">Cap. Volumen</th>
                                <th className="px-5 py-3.5 text-center">Estado</th>
                                <th className="px-5 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {rows.map((vehicle) => (
                                <tr
                                    key={vehicle.id}
                                    className="transition-colors hover:bg-muted/30"
                                >
                                    <td className="px-5 py-3.5">
                                        <div className="flex flex-col">
                                            <span className="font-mono font-bold text-foreground">
                                                {vehicle.plate}
                                            </span>
                                            <span className="text-[12px] text-muted-foreground">
                                                {vehicle.code}
                                            </span>
                                        </div>
                                    </td>
                                    <td className="px-5 py-3.5 font-medium">
                                        <Link
                                            href={
                                                vehicles.show({
                                                    company: companyId,
                                                    id: vehicle.id,
                                                }).url
                                            }
                                            className="font-bold text-foreground hover:text-primary hover:underline"
                                        >
                                            {vehicle.name}
                                        </Link>
                                    </td>
                                    <td className="px-5 py-3.5 text-muted-foreground">
                                        {[vehicle.brand, vehicle.model, vehicle.year]
                                            .filter(Boolean)
                                            .join(' · ') || '—'}
                                    </td>
                                    <td className="px-5 py-3.5 text-muted-foreground">
                                        {vehicle.vehicle_type_label ||
                                            VEHICLE_TYPE_LABELS[vehicle.vehicle_type] ||
                                            vehicle.vehicle_type}
                                    </td>
                                    <td className="px-5 py-3.5 text-right font-medium tabular-nums">
                                        {vehicle.capacity_weight > 0
                                            ? `${vehicle.capacity_weight.toLocaleString()} kg`
                                            : '—'}
                                    </td>
                                    <td className="px-5 py-3.5 text-right font-medium tabular-nums">
                                        {vehicle.capacity_volume > 0
                                            ? `${vehicle.capacity_volume.toLocaleString()} m³`
                                            : '—'}
                                    </td>
                                    <td className="px-5 py-3.5 text-center">
                                        <StatusPill
                                            kind={STATUS_PILL_KIND[vehicle.status]}
                                        >
                                            {STATUS_LABELS[vehicle.status]}
                                        </StatusPill>
                                    </td>
                                    <td className="px-5 py-3.5 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 rounded-lg"
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
                                                        className="flex items-center gap-2"
                                                    >
                                                        <Eye className="size-4" />
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
                                                        className="flex items-center gap-2"
                                                    >
                                                        <Edit className="size-4" />
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
                                                        className="flex items-center gap-2 text-primary"
                                                    >
                                                        <Power className="size-4" />
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
                                                        className="flex items-center gap-2 text-warning"
                                                    >
                                                        <Wrench className="size-4" />
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
                                                        className="flex items-center gap-2 text-bad"
                                                    >
                                                        <Power className="size-4" />
                                                        Desactivar
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            ))}
                            {rows.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={8}
                                        className="py-12 text-center text-muted-foreground"
                                    >
                                        <Truck className="mx-auto mb-2 size-8 opacity-40" />
                                        No se encontraron camiones registrados con los filtros aplicados.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="border-t p-4">
                    <Pagination
                        meta={meta}
                        onPageChange={(newOffset) =>
                            applyFilters({ ...filters, offset: newOffset })
                        }
                        label="camión"
                        labelPlural="camiones"
                    />
                </div>
            </Card>
        </div>
    );
}
