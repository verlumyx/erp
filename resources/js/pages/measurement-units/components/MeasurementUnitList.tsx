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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import measurementUnits from '@/routes/measurement-units';
import type {
    MeasurementUnit,
    MeasurementUnitFilters,
    MeasurementUnitMeta,
} from '../types/MeasurementUnit';

interface MeasurementUnitListProps {
    measurementUnits: MeasurementUnit[];
    meta: MeasurementUnitMeta;
    filters: MeasurementUnitFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function MeasurementUnitList({
    measurementUnits: items,
    meta,
    filters: initialFilters,
}: MeasurementUnitListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] =
        useState<MeasurementUnitFilters>(initialFilters);

    const applyFilters = (next: MeasurementUnitFilters) => {
        setFilters(next);
        router.get(
            measurementUnits.index(companyId).url,
            next as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(measurementUnits.index(companyId).url);
    };

    const handleToggleStatus = (unit: MeasurementUnit) => {
        router.put(
            measurementUnits.updateStatus({
                company: companyId,
                id: unit.id,
            }).url,
            {
                status: unit.status === 'active' ? 'inactive' : 'active',
            },
        );
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Unidades de medida
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} unidad{meta.total !== 1 ? 'es' : ''}
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() =>
                        router.visit(measurementUnits.create(companyId).url)
                    }
                >
                    <Plus />
                    Nueva unidad
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {(
                        [
                            ['name', 'Nombre'],
                            ['abbreviation', 'Símbolo'],
                            ['code', 'Código'],
                        ] as Array<[keyof MeasurementUnitFilters, string]>
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
                        <Label htmlFor="filter-status">Estado</Label>
                        <Select
                            value={filters.status ?? 'todos'}
                            onValueChange={(value) =>
                                applyFilters({
                                    ...filters,
                                    status:
                                        value === 'todos' ? undefined : value,
                                })
                            }
                        >
                            <SelectTrigger
                                id="filter-status"
                                className="w-full"
                            >
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos</SelectItem>
                                <SelectItem value="active">Activas</SelectItem>
                                <SelectItem value="inactive">
                                    Inactivas
                                </SelectItem>
                            </SelectContent>
                        </Select>
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.2fr_0.8fr_0.9fr_1fr]">
                    {['Código', 'Nombre', 'Símbolo', 'Estado', 'Acciones'].map(
                        (h, i) => (
                            <div
                                key={h}
                                className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                    i === 4 ? 'text-right' : ''
                                }`}
                            >
                                {h}
                            </div>
                        ),
                    )}
                </div>
                <div className="flex flex-col">
                    {items.map((unit) => (
                        <div
                            key={unit.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.2fr_0.8fr_0.9fr_1fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    measurementUnits.show({
                                        company: companyId,
                                        id: unit.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {unit.code}
                                </span>
                            </div>
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {unit.name}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {unit.description ?? '—'}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <span className="font-semibold">
                                    {unit.abbreviation}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={
                                        unit.status === 'inactive'
                                            ? 'inactivo'
                                            : 'activo'
                                    }
                                />
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
                                                    measurementUnits.show({
                                                        company: companyId,
                                                        id: unit.id,
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
                                                    measurementUnits.edit({
                                                        company: companyId,
                                                        id: unit.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Edit className="mr-2 h-4 w-4" />
                                            Editar
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            onClick={() =>
                                                handleToggleStatus(unit)
                                            }
                                        >
                                            <Power className="mr-2 h-4 w-4" />
                                            {unit.status === 'active'
                                                ? 'Inactivar'
                                                : 'Activar'}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    ))}
                    {items.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Sin resultados para tu búsqueda.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {items.length} de {meta.total} unidad
                    {meta.total !== 1 ? 'es' : ''}
                </div>
            </Card>
        </div>
    );
}
