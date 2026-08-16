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
import categories from '@/routes/categories';
import type {
    Category,
    CategoryFilters,
    CategoryMeta,
} from '../types/Category';

interface CategoryListProps {
    categories: Category[];
    meta: CategoryMeta;
    filters: CategoryFilters;
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

export function CategoryList({
    categories: items,
    meta,
    filters: initialFilters,
}: CategoryListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<CategoryFilters>(initialFilters);

    const applyFilters = (next: CategoryFilters) => {
        setFilters(next);
        router.get(
            categories.index(companyId).url,
            next as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(categories.index(companyId).url);
    };

    const handleToggleStatus = (category: Category) => {
        router.put(
            categories.updateStatus({ company: companyId, id: category.id })
                .url,
            {
                status: category.status === 'active' ? 'inactive' : 'active',
            },
        );
    };

    const rows = items;

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Categorías
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} categoría{meta.total !== 1 ? 's' : ''}
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() =>
                        router.visit(categories.create(companyId).url)
                    }
                >
                    <Plus />
                    Nueva categoría
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {(
                        [
                            ['name', 'Nombre'],
                            ['description', 'Descripción'],
                            ['code', 'Código'],
                        ] as Array<[keyof CategoryFilters, string]>
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.2fr_0.6fr_0.9fr_1fr]">
                    {['Código', 'Categoría', 'Orden', 'Estado', 'Acciones'].map(
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
                    {rows.map((category) => {
                        const estadoCategoria =
                            category.status === 'inactive'
                                ? 'inactivo'
                                : 'activo';
                        return (
                            <div
                                key={category.id}
                                className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.2fr_0.6fr_0.9fr_1fr] lg:py-0"
                                onClick={() =>
                                    router.visit(
                                        categories.show({
                                            company: companyId,
                                            id: category.id,
                                        }).url,
                                    )
                                }
                            >
                                <div className="hidden lg:block">
                                    <span className="font-semibold text-muted-foreground tabular-nums">
                                        {category.code}
                                    </span>
                                </div>
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {category.name}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {category.description ?? '—'}
                                    </span>
                                </div>
                                <div className="hidden lg:block">
                                    <span className="font-semibold text-muted-foreground tabular-nums">
                                        {category.order}
                                    </span>
                                </div>
                                <div className="hidden lg:block">
                                    <StatusPill kind={estadoCategoria} />
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
                                                        categories.show({
                                                            company: companyId,
                                                            id: category.id,
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
                                                        categories.edit({
                                                            company: companyId,
                                                            id: category.id,
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
                                                    handleToggleStatus(category)
                                                }
                                            >
                                                <Power className="mr-2 h-4 w-4" />
                                                {category.status === 'active'
                                                    ? 'Inactivar'
                                                    : 'Activar'}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        );
                    })}
                    {rows.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Sin resultados para tu búsqueda.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {rows.length} de {meta.total} categoría
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
