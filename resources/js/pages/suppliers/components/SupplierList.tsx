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
import suppliers from '@/routes/suppliers';
import {
    formatDocument,
    type Supplier,
    type SupplierFilters,
    type SupplierMeta,
    type SupplierOptions,
} from '../types/Supplier';

interface SupplierListProps {
    suppliers: Supplier[];
    meta: SupplierMeta;
    filters: SupplierFilters;
    options: SupplierOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const ALL = 'todos';

export function SupplierList({
    suppliers: rows,
    meta,
    filters: initialFilters,
    options,
}: SupplierListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<SupplierFilters>(initialFilters);

    const applyFilters = (next: SupplierFilters) => {
        setFilters(next);
        router.get(
            suppliers.index(companyId).url,
            next as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(suppliers.index(companyId).url);
    };

    const handleToggleStatus = (supplier: Supplier) => {
        router.put(
            suppliers.updateStatus({ company: companyId, id: supplier.id }).url,
            {
                status: supplier.status === 'active' ? 'inactive' : 'active',
            },
        );
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Proveedores
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} proveedor{meta.total !== 1 ? 'es' : ''}
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() =>
                        router.visit(suppliers.create(companyId).url)
                    }
                >
                    <Plus />
                    Nuevo proveedor
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {(
                        [
                            ['name', 'Nombre'],
                            ['code', 'Código'],
                            ['document_number', 'RIF'],
                            ['email', 'Correo'],
                        ] as Array<[keyof SupplierFilters, string]>
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
                        <Label htmlFor="filter-supplier-type">Tipo</Label>
                        <Select
                            value={filters.supplier_type_id ?? ALL}
                            onValueChange={(value) =>
                                applyFilters({
                                    ...filters,
                                    supplier_type_id:
                                        value === ALL ? undefined : value,
                                })
                            }
                        >
                            <SelectTrigger
                                id="filter-supplier-type"
                                className="w-full"
                            >
                                <SelectValue placeholder="Tipo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>Todos</SelectItem>
                                {options.supplierTypes.map((supplierType) => (
                                    <SelectItem
                                        key={supplierType.id}
                                        value={supplierType.id}
                                    >
                                        {supplierType.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-status">Estado</Label>
                        <Select
                            value={filters.status ?? ALL}
                            onValueChange={(value) =>
                                applyFilters({
                                    ...filters,
                                    status: value === ALL ? undefined : value,
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
                                <SelectItem value={ALL}>Todos</SelectItem>
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.6fr_1.2fr_1fr_0.9fr_1fr]">
                    {[
                        'Código',
                        'Proveedor',
                        'RIF',
                        'Saldo',
                        'Estado',
                        'Acciones',
                    ].map((h, i) => (
                        <div
                            key={h}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                i === 5 ? 'text-right' : ''
                            }`}
                        >
                            {h}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {rows.map((supplier) => (
                        <div
                            key={supplier.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.6fr_1.2fr_1fr_0.9fr_1fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    suppliers.show({
                                        company: companyId,
                                        id: supplier.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {supplier.code}
                                </span>
                            </div>
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {supplier.name}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {supplier.supplier_type_name ??
                                        supplier.email ??
                                        '—'}
                                </span>
                            </div>
                            <div className="hidden text-[13.5px] font-medium text-muted-foreground tabular-nums lg:block">
                                {formatDocument(
                                    supplier.document_type,
                                    supplier.document_number,
                                )}
                            </div>
                            <div className="hidden text-[13.5px] font-semibold tabular-nums lg:block">
                                {supplier.current_balance}
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={
                                        supplier.status === 'inactive'
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
                                                    suppliers.show({
                                                        company: companyId,
                                                        id: supplier.id,
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
                                                    suppliers.edit({
                                                        company: companyId,
                                                        id: supplier.id,
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
                                                handleToggleStatus(supplier)
                                            }
                                        >
                                            <Power className="mr-2 h-4 w-4" />
                                            {supplier.status === 'active'
                                                ? 'Inactivar'
                                                : 'Activar'}
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
                    {rows.length} de {meta.total} proveedor
                    {meta.total !== 1 ? 'es' : ''}
                </div>
            </Card>
        </div>
    );
}
