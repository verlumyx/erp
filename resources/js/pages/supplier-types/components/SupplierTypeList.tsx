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
import supplierTypes from '@/routes/supplier-types';
import type {
    SupplierType,
    SupplierTypeFilters,
    SupplierTypeMeta,
} from '../types/SupplierType';

interface SupplierTypeListProps {
    supplierTypes: SupplierType[];
    meta: SupplierTypeMeta;
    filters: SupplierTypeFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function SupplierTypeList({
    supplierTypes: items,
    meta,
    filters: initialFilters,
}: SupplierTypeListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<SupplierTypeFilters>(initialFilters);

    const applyFilters = (next: SupplierTypeFilters) => {
        setFilters(next);
        router.get(
            supplierTypes.index(companyId).url,
            next as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(supplierTypes.index(companyId).url);
    };

    const handleToggleStatus = (supplierType: SupplierType) => {
        router.put(
            supplierTypes.updateStatus({
                company: companyId,
                id: supplierType.id,
            }).url,
            {
                status:
                    supplierType.status === 'active' ? 'inactive' : 'active',
            },
        );
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Tipos de proveedor
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} tipo{meta.total !== 1 ? 's' : ''}
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() =>
                        router.visit(supplierTypes.create(companyId).url)
                    }
                >
                    <Plus />
                    Nuevo tipo
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {(
                        [
                            ['name', 'Nombre'],
                            ['code', 'Código'],
                        ] as Array<[keyof SupplierTypeFilters, string]>
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
                                <SelectItem value="active">Activos</SelectItem>
                                <SelectItem value="inactive">
                                    Inactivos
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.6fr_0.9fr_1fr]">
                    {['Código', 'Nombre', 'Estado', 'Acciones'].map((h, i) => (
                        <div
                            key={h}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                i === 3 ? 'text-right' : ''
                            }`}
                        >
                            {h}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {items.map((supplierType) => (
                        <div
                            key={supplierType.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.6fr_0.9fr_1fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    supplierTypes.show({
                                        company: companyId,
                                        id: supplierType.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {supplierType.code}
                                </span>
                            </div>
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {supplierType.name}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {supplierType.description ?? '—'}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={
                                        supplierType.status === 'inactive'
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
                                                    supplierTypes.show({
                                                        company: companyId,
                                                        id: supplierType.id,
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
                                                    supplierTypes.edit({
                                                        company: companyId,
                                                        id: supplierType.id,
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
                                                handleToggleStatus(supplierType)
                                            }
                                        >
                                            <Power className="mr-2 h-4 w-4" />
                                            {supplierType.status === 'active'
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
                    {items.length} de {meta.total} tipo
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
