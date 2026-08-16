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
import warehouses from '@/routes/warehouses';
import {
    WAREHOUSE_TYPE_LABELS,
    type Warehouse,
    type WarehouseFilters,
    type WarehouseMeta,
} from '../types/Warehouse';

interface WarehouseListProps {
    warehouses: Warehouse[];
    meta: WarehouseMeta;
    filters: WarehouseFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function WarehouseList({
    warehouses: items,
    meta,
    filters: initialFilters,
}: WarehouseListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<WarehouseFilters>(initialFilters);

    const applyFilters = (next: WarehouseFilters) => {
        setFilters(next);
        router.get(
            warehouses.index(companyId).url,
            next as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(warehouses.index(companyId).url);
    };

    const handleToggleStatus = (warehouse: Warehouse) => {
        router.put(
            warehouses.updateStatus({ company: companyId, id: warehouse.id })
                .url,
            {
                status: warehouse.status === 'active' ? 'inactive' : 'active',
            },
        );
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Bodegas
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} bodega{meta.total !== 1 ? 's' : ''}
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() =>
                        router.visit(warehouses.create(companyId).url)
                    }
                >
                    <Plus />
                    Nueva bodega
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {(
                        [
                            ['name', 'Nombre'],
                            ['city', 'Ciudad'],
                            ['code', 'Código'],
                        ] as Array<[keyof WarehouseFilters, string]>
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
                        <Label htmlFor="filter-type">Tipo</Label>
                        <Select
                            value={filters.type ?? 'todos'}
                            onValueChange={(value) =>
                                applyFilters({
                                    ...filters,
                                    type: value === 'todos' ? undefined : value,
                                })
                            }
                        >
                            <SelectTrigger id="filter-type" className="w-full">
                                <SelectValue placeholder="Tipo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos</SelectItem>
                                {Object.entries(WAREHOUSE_TYPE_LABELS).map(
                                    ([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>
                    </div>
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.2fr_0.9fr_0.9fr_1fr]">
                    {['Código', 'Bodega', 'Tipo', 'Estado', 'Acciones'].map(
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
                    {items.map((warehouse) => {
                        const estadoBodega =
                            warehouse.status === 'inactive'
                                ? 'inactivo'
                                : 'activo';
                        return (
                            <div
                                key={warehouse.id}
                                className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.2fr_0.9fr_0.9fr_1fr] lg:py-0"
                                onClick={() =>
                                    router.visit(
                                        warehouses.show({
                                            company: companyId,
                                            id: warehouse.id,
                                        }).url,
                                    )
                                }
                            >
                                <div className="hidden lg:block">
                                    <span className="font-semibold text-muted-foreground tabular-nums">
                                        {warehouse.code}
                                    </span>
                                </div>
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {warehouse.name}
                                        {warehouse.is_default === 'yes' && (
                                            <span className="ml-2 rounded-full bg-primary-soft px-2 py-0.5 text-[11px] font-bold text-primary">
                                                Por defecto
                                            </span>
                                        )}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {warehouse.city ?? '—'}
                                        {warehouse.responsible_user_name
                                            ? ` · ${warehouse.responsible_user_name}`
                                            : ''}
                                    </span>
                                </div>
                                <div className="hidden lg:block">
                                    <span className="text-[13px] font-semibold text-muted-foreground">
                                        {WAREHOUSE_TYPE_LABELS[warehouse.type]}
                                    </span>
                                </div>
                                <div className="hidden lg:block">
                                    <StatusPill kind={estadoBodega} />
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
                                                        warehouses.show({
                                                            company: companyId,
                                                            id: warehouse.id,
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
                                                        warehouses.edit({
                                                            company: companyId,
                                                            id: warehouse.id,
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
                                                    handleToggleStatus(
                                                        warehouse,
                                                    )
                                                }
                                            >
                                                <Power className="mr-2 h-4 w-4" />
                                                {warehouse.status === 'active'
                                                    ? 'Inactivar'
                                                    : 'Activar'}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        );
                    })}
                    {items.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Sin resultados para tu búsqueda.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {items.length} de {meta.total} bodega
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
