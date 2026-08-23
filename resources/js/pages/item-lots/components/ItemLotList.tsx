import { router, usePage } from '@inertiajs/react';
import { Edit, Eye, Lock, MoreHorizontal, Search, Unlock } from 'lucide-react';
import { useState } from 'react';
import { Select2Ajax, type AjaxOption } from '@/components/select2-ajax';
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
import itemLots from '@/routes/item-lots';
import items from '@/routes/items';
import {
    LOT_STATUS_LABELS,
    LOT_STATUS_PILL,
    type ItemLot,
    type ItemLotFilters,
    type ItemLotMeta,
} from '../types/ItemLot';

interface ItemLotListProps {
    lots: ItemLot[];
    meta: ItemLotMeta;
    filters: ItemLotFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const STATUS_OPTIONS: OptionType[] = [
    { value: 'todos', label: 'Todos' },
    { value: 'active', label: 'Activos' },
    { value: 'blocked', label: 'Retenidos' },
    { value: 'expired', label: 'Vencidos' },
];

export function ItemLotList({
    lots,
    meta,
    filters: initialFilters,
}: ItemLotListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<ItemLotFilters>(initialFilters);
    const [itemOption, setItemOption] = useState<AjaxOption | null>(null);

    const applyFilters = (next: ItemLotFilters) => {
        setFilters(next);
        router.get(
            itemLots.index(companyId).url,
            next as Record<string, string>,
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleClear = () => {
        setFilters({});
        setItemOption(null);
        router.get(itemLots.index(companyId).url);
    };

    /** Retener suelta lo contrario de liberar; un lote vencido no se reactiva. */
    const handleToggleStatus = (lot: ItemLot) => {
        router.put(
            itemLots.updateStatus({ company: companyId, id: lot.id }).url,
            {
                status: lot.status === 'active' ? 'blocked' : 'active',
            },
        );
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Lotes
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} lote{meta.total !== 1 ? 's' : ''} · se
                        registran al recibir la mercancía; la salida consume
                        primero lo que vence antes
                    </p>
                </div>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2">
                        <Label htmlFor="filter-lot-number">
                            Número de lote
                        </Label>
                        <Input
                            id="filter-lot-number"
                            type="text"
                            placeholder="Buscar por número..."
                            value={filters.lot_number ?? ''}
                            onChange={(e) =>
                                setFilters({
                                    ...filters,
                                    lot_number: e.target.value,
                                })
                            }
                            onKeyDown={(e) =>
                                e.key === 'Enter' && applyFilters(filters)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-item">Artículo</Label>
                        <Select2Ajax
                            inputId="filter-item"
                            url={items.lookup(companyId).url}
                            value={itemOption}
                            onChange={(option) => {
                                setItemOption(option);
                                applyFilters({
                                    ...filters,
                                    item_id: option?.value,
                                });
                            }}
                            isClearable
                            placeholder="Todos"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-expires-before">
                            Vence antes de
                        </Label>
                        <Input
                            id="filter-expires-before"
                            type="date"
                            value={filters.expires_before ?? ''}
                            onChange={(e) =>
                                applyFilters({
                                    ...filters,
                                    expires_before: e.target.value || undefined,
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.2fr_1.4fr_1fr_0.9fr_0.8fr]">
                    {[
                        'Código',
                        'Lote',
                        'Artículo',
                        'Vence',
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
                    {lots.map((lot) => (
                        <div
                            key={lot.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.2fr_1.4fr_1fr_0.9fr_0.8fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    itemLots.show({
                                        company: companyId,
                                        id: lot.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {lot.code}
                                </span>
                            </div>
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {lot.lot_number}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {lot.supplier_name
                                        ? `de ${lot.supplier_name}`
                                        : 'Lote interno'}
                                </span>
                            </div>
                            <div className="hidden min-w-0 lg:block">
                                <span className="truncate text-[13px] font-semibold text-muted-foreground">
                                    {lot.item_name ?? '—'}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <span className="text-[13px] font-semibold text-muted-foreground tabular-nums">
                                    {lot.expires_at ?? '—'}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill kind={LOT_STATUS_PILL[lot.status]}>
                                    {LOT_STATUS_LABELS[lot.status]}
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
                                            onClick={() =>
                                                router.visit(
                                                    itemLots.show({
                                                        company: companyId,
                                                        id: lot.id,
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
                                                    itemLots.edit({
                                                        company: companyId,
                                                        id: lot.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Edit className="mr-2 h-4 w-4" />
                                            Editar
                                        </DropdownMenuItem>
                                        {lot.status !== 'expired' && (
                                            <>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        handleToggleStatus(lot)
                                                    }
                                                >
                                                    {lot.status === 'active' ? (
                                                        <>
                                                            <Lock className="mr-2 h-4 w-4" />
                                                            Retener
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Unlock className="mr-2 h-4 w-4" />
                                                            Liberar
                                                        </>
                                                    )}
                                                </DropdownMenuItem>
                                            </>
                                        )}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    ))}
                    {lots.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Sin resultados para tu búsqueda.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {lots.length} de {meta.total} lote
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
