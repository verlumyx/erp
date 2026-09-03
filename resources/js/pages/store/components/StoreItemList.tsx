import { router, usePage } from '@inertiajs/react';
import {
    Edit,
    Eye,
    ImageOff,
    MoreHorizontal,
    Plus,
    Power,
    Search,
    Star,
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
import { formatMoney } from '@/lib/money';
import storeItems from '@/routes/store-items';
import { useStorePermissions } from '../hooks/useStorePermissions';
import type { ListMeta, StoreItem, StoreItemFilters } from '../types/Store';

interface StoreItemListProps {
    storeItems: StoreItem[];
    meta: ListMeta;
    filters: StoreItemFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const ALL = 'todos';

const STATUS_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todas' },
    { value: 'active', label: 'Visible' },
    { value: 'inactive', label: 'Oculta' },
];

const FEATURED_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todas' },
    { value: 'yes', label: 'Destacadas' },
    { value: 'no', label: 'No destacadas' },
];

function cover(item: StoreItem) {
    return [...item.images]
        .filter((image) => image.status === 'active')
        .sort((a, b) => a.order - b.order)[0];
}

export function StoreItemList({
    storeItems: rows,
    meta,
    filters: initialFilters,
}: StoreItemListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const { can } = useStorePermissions();

    const [filters, setFilters] = useState<StoreItemFilters>(initialFilters);

    const applyFilters = (next: StoreItemFilters) => {
        setFilters(next);
        router.get(
            storeItems.index(companyId).url,
            next as Record<string, string>,
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(storeItems.index(companyId).url);
    };

    const toggleStatus = (row: StoreItem) => {
        router.put(
            storeItems.updateStatus({ company: companyId, id: row.id }).url,
            { status: row.status === 'active' ? 'inactive' : 'active' },
            { preserveScroll: true },
        );
    };

    const goTo = (row: StoreItem) =>
        router.visit(storeItems.show({ company: companyId, id: row.id }).url);

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Publicaciones
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} artículo{meta.total !== 1 ? 's' : ''}{' '}
                        publicado{meta.total !== 1 ? 's' : ''} en la tienda
                    </p>
                </div>
                {can('store-items.create') && (
                    <Button
                        className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                        onClick={() =>
                            router.visit(storeItems.create(companyId).url)
                        }
                    >
                        <Plus />
                        Publicar artículo
                    </Button>
                )}
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2 lg:col-span-2">
                        <Label htmlFor="filter-q">Buscar</Label>
                        <Input
                            id="filter-q"
                            type="text"
                            placeholder="Título, código, SKU o nombre del artículo…"
                            value={filters.q ?? ''}
                            onChange={(e) =>
                                setFilters({ ...filters, q: e.target.value })
                            }
                            onKeyDown={(e) =>
                                e.key === 'Enter' && applyFilters(filters)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-featured">Destacado</Label>
                        <Select2
                            inputId="filter-featured"
                            options={FEATURED_OPTIONS}
                            value={
                                FEATURED_OPTIONS.find(
                                    (option) =>
                                        option.value ===
                                        (filters.is_featured ?? ALL),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    is_featured:
                                        !option || option.value === ALL
                                            ? undefined
                                            : option.value,
                                })
                            }
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
                        />
                    </div>
                </div>
                <div className="mt-4 flex justify-end gap-2">
                    <Button onClick={() => applyFilters(filters)}>
                        <Search className="mr-2 h-4 w-4" />
                        Buscar
                    </Button>
                    <Button onClick={handleClear} variant="outline">
                        Limpiar
                    </Button>
                </div>
            </div>

            <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.5fr_0.9fr_2.4fr_1fr_1fr_1fr_0.8fr]">
                    {[
                        '',
                        'Código',
                        'Publicación',
                        'Precio',
                        'Disponibilidad',
                        'Estado',
                        'Acciones',
                    ].map((header, index) => (
                        <div
                            key={`${header}-${index}`}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${index === 6 ? 'text-right' : ''}`}
                        >
                            {header}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {rows.map((row) => {
                        const image = cover(row);

                        return (
                            <div
                                key={row.id}
                                className="grid min-h-[66px] cursor-pointer grid-cols-[auto_1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.5fr_0.9fr_2.4fr_1fr_1fr_1fr_0.8fr] lg:py-2"
                                onClick={() => goTo(row)}
                            >
                                <div className="grid size-12 shrink-0 place-items-center overflow-hidden rounded-[10px] border bg-muted">
                                    {image ? (
                                        <img
                                            src={image.url}
                                            alt={image.alt_text ?? row.title}
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <ImageOff className="size-4 text-muted-foreground" />
                                    )}
                                </div>
                                <div className="hidden lg:block">
                                    <span className="font-semibold text-muted-foreground tabular-nums">
                                        {row.code}
                                    </span>
                                </div>
                                <div className="flex min-w-0 flex-col">
                                    <span className="flex items-center gap-1.5 truncate font-bold">
                                        {row.is_featured === 'yes' && (
                                            <Star className="size-3.5 shrink-0 fill-warn text-warn" />
                                        )}
                                        <span className="truncate">
                                            {row.title}
                                        </span>
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {row.item?.code ?? '—'}
                                        {row.item?.sku
                                            ? ` · ${row.item.sku}`
                                            : ''}
                                        {row.item?.category
                                            ? ` · ${row.item.category.name}`
                                            : ''}
                                        {' · '}/{row.slug}
                                    </span>
                                </div>
                                <div className="hidden text-[13.5px] font-semibold tabular-nums lg:block">
                                    {row.price
                                        ? formatMoney(
                                              row.price.amount,
                                              row.price.currency,
                                          )
                                        : 'Consultar'}
                                </div>
                                <div className="hidden text-[13.5px] lg:block">
                                    {row.availability
                                        ? row.availability.in_stock === 'yes'
                                            ? `Disponible${row.availability.quantity ? ` · ${row.availability.quantity}` : ''}`
                                            : 'Agotado'
                                        : '—'}
                                </div>
                                <div className="hidden lg:block">
                                    <StatusPill
                                        kind={
                                            row.status === 'active'
                                                ? 'activo'
                                                : 'inactivo'
                                        }
                                    >
                                        {row.status === 'active'
                                            ? 'Visible'
                                            : 'Oculta'}
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
                                                onClick={() => goTo(row)}
                                            >
                                                <Eye className="mr-2 h-4 w-4" />
                                                Ver
                                            </DropdownMenuItem>
                                            {can('store-items.edit') && (
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        router.visit(
                                                            storeItems.edit({
                                                                company:
                                                                    companyId,
                                                                id: row.id,
                                                            }).url,
                                                        )
                                                    }
                                                >
                                                    <Edit className="mr-2 h-4 w-4" />
                                                    Editar
                                                </DropdownMenuItem>
                                            )}
                                            {can(
                                                'store-items.update-status',
                                            ) && (
                                                <>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        onClick={() =>
                                                            toggleStatus(row)
                                                        }
                                                    >
                                                        <Power className="mr-2 h-4 w-4" />
                                                        {row.status === 'active'
                                                            ? 'Ocultar'
                                                            : 'Mostrar'}
                                                    </DropdownMenuItem>
                                                </>
                                            )}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        );
                    })}
                    {rows.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Todavía no hay publicaciones.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {rows.length} de {meta.total} publicaci
                    {meta.total !== 1 ? 'ones' : 'ón'}
                </div>
            </Card>
        </div>
    );
}
