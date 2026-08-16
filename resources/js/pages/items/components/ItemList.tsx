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
import items from '@/routes/items';
import {
    ITEM_TYPE_LABELS,
    type Item,
    type ItemFilters,
    type ItemMeta,
    type ItemOptions,
    type ItemType,
} from '../types/Item';

interface ItemListProps {
    items: Item[];
    meta: ItemMeta;
    filters: ItemFilters;
    options: ItemOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const ALL = 'todos';

const TYPE_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todos' },
    ...Object.entries(ITEM_TYPE_LABELS).map(([value, label]) => ({
        value,
        label,
    })),
];

const STATUS_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todos' },
    { value: 'active', label: 'Activos' },
    { value: 'inactive', label: 'Inactivos' },
];

export function ItemList({
    items: rows,
    meta,
    filters: initialFilters,
    options,
}: ItemListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<ItemFilters>(initialFilters);

    const categoryOptions: OptionType[] = [
        { value: ALL, label: 'Todas' },
        ...options.categories.map((category) => ({
            value: category.id,
            label: category.name,
        })),
    ];

    const applyFilters = (next: ItemFilters) => {
        setFilters(next);
        router.get(items.index(companyId).url, next as Record<string, string>, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleClear = () => {
        setFilters({});
        router.get(items.index(companyId).url);
    };

    const handleToggleStatus = (item: Item) => {
        router.put(
            items.updateStatus({ company: companyId, id: item.id }).url,
            {
                status: item.status === 'active' ? 'inactive' : 'active',
            },
        );
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Catálogo de artículos
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} artículo{meta.total !== 1 ? 's' : ''}
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() => router.visit(items.create(companyId).url)}
                >
                    <Plus />
                    Nuevo artículo
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {(
                        [
                            ['name', 'Nombre'],
                            ['sku', 'SKU'],
                            ['barcode', 'Código de barras'],
                            ['code', 'Código'],
                        ] as Array<[keyof ItemFilters, string]>
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
                        <Select2
                            inputId="filter-type"
                            options={TYPE_OPTIONS}
                            value={
                                TYPE_OPTIONS.find(
                                    (option) =>
                                        option.value === (filters.type ?? ALL),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    type:
                                        !option || option.value === ALL
                                            ? undefined
                                            : option.value,
                                })
                            }
                            placeholder="Tipo"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-category">Categoría</Label>
                        <Select2
                            inputId="filter-category"
                            options={categoryOptions}
                            value={
                                categoryOptions.find(
                                    (option) =>
                                        option.value ===
                                        (filters.category_id ?? ALL),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    category_id:
                                        !option || option.value === ALL
                                            ? undefined
                                            : option.value,
                                })
                            }
                            placeholder="Categoría"
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
                                        (filters.status ?? ALL),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    status:
                                        !option || option.value === ALL
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.6fr_1.1fr_0.9fr_1fr]">
                    {['Código', 'Artículo', 'Tipo', 'Estado', 'Acciones'].map(
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
                    {rows.map((item) => (
                        <div
                            key={item.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.6fr_1.1fr_0.9fr_1fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    items.show({
                                        company: companyId,
                                        id: item.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {item.code}
                                </span>
                            </div>
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {item.name}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {item.sku}
                                    {item.category_name
                                        ? ` · ${item.category_name}`
                                        : ''}
                                </span>
                            </div>
                            <div className="hidden text-[13.5px] font-medium text-muted-foreground lg:block">
                                {ITEM_TYPE_LABELS[item.type as ItemType]}
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={
                                        item.status === 'inactive'
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
                                                    items.show({
                                                        company: companyId,
                                                        id: item.id,
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
                                                    items.edit({
                                                        company: companyId,
                                                        id: item.id,
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
                                                handleToggleStatus(item)
                                            }
                                        >
                                            <Power className="mr-2 h-4 w-4" />
                                            {item.status === 'active'
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
                    {rows.length} de {meta.total} artículo
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
