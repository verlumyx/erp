import { router, usePage } from '@inertiajs/react';
import { ArrowDownLeft, ArrowUpRight, Search } from 'lucide-react';
import { useState } from 'react';
import { Select2Ajax, type AjaxOption } from '@/components/select2-ajax';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import inventoryMovements from '@/routes/inventory-movements';
import items from '@/routes/items';
import {
    formatAmount,
    formatQuantity,
    MOVEMENT_TYPE_LABELS,
    originLabel,
    signedQuantity,
    type InventoryMovement,
    type InventoryMovementFilters,
    type InventoryMovementMeta,
    type WarehouseOption,
} from '../types/InventoryMovement';

interface InventoryMovementListProps {
    movements: InventoryMovement[];
    warehouses: WarehouseOption[];
    meta: InventoryMovementMeta;
    filters: InventoryMovementFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const DIRECTION_OPTIONS: OptionType[] = [
    { value: 'todos', label: 'Todos' },
    { value: 'in', label: 'Entradas' },
    { value: 'out', label: 'Salidas' },
];

const TYPE_OPTIONS: OptionType[] = [
    { value: 'todos', label: 'Todos' },
    ...Object.entries(MOVEMENT_TYPE_LABELS).map(([value, label]) => ({
        value,
        label,
    })),
];

const STATUS_OPTIONS: OptionType[] = [
    { value: 'todos', label: 'Todos' },
    { value: 'active', label: 'Vigentes' },
    { value: 'reversed', label: 'Revertidos' },
];

export function InventoryMovementList({
    movements,
    warehouses,
    meta,
    filters: initialFilters,
}: InventoryMovementListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] =
        useState<InventoryMovementFilters>(initialFilters);
    const [itemOption, setItemOption] = useState<AjaxOption | null>(null);

    const warehouseOptions: OptionType[] = [
        { value: 'todas', label: 'Todas' },
        ...warehouses.map((warehouse) => ({
            value: warehouse.id,
            label: warehouse.name,
        })),
    ];

    const applyFilters = (next: InventoryMovementFilters) => {
        setFilters(next);
        router.get(
            inventoryMovements.index(companyId).url,
            next as Record<string, string>,
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleClear = () => {
        setFilters({});
        setItemOption(null);
        router.get(inventoryMovements.index(companyId).url);
    };

    /** `todos` es el valor neutro del select: no viaja como filtro. */
    const pick = (option: OptionType | null, neutral: string) =>
        !option || option.value === neutral ? undefined : option.value;

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Kardex
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} movimiento{meta.total !== 1 ? 's' : ''} ·
                        un error se corrige con la contrapartida, nunca editando
                        el original
                    </p>
                </div>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2">
                        <Label htmlFor="filter-q">Buscar</Label>
                        <Input
                            id="filter-q"
                            type="text"
                            placeholder="Código, artículo o SKU..."
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
                        <Label htmlFor="filter-warehouse">Bodega</Label>
                        <Select2
                            inputId="filter-warehouse"
                            options={warehouseOptions}
                            value={
                                warehouseOptions.find(
                                    (option) =>
                                        option.value ===
                                        (filters.warehouse_id ?? 'todas'),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    warehouse_id: pick(option, 'todas'),
                                })
                            }
                            placeholder="Bodega"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-direction">Sentido</Label>
                        <Select2
                            inputId="filter-direction"
                            options={DIRECTION_OPTIONS}
                            value={
                                DIRECTION_OPTIONS.find(
                                    (option) =>
                                        option.value ===
                                        (filters.direction ?? 'todos'),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    direction: pick(option, 'todos'),
                                })
                            }
                            placeholder="Sentido"
                            isSearchable={false}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-type">Tipo</Label>
                        <Select2
                            inputId="filter-type"
                            options={TYPE_OPTIONS}
                            value={
                                TYPE_OPTIONS.find(
                                    (option) =>
                                        option.value ===
                                        (filters.type ?? 'todos'),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    type: pick(option, 'todos'),
                                })
                            }
                            placeholder="Tipo"
                            isSearchable={false}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-date-from">Desde</Label>
                        <Input
                            id="filter-date-from"
                            type="date"
                            value={filters.date_from ?? ''}
                            onChange={(e) =>
                                applyFilters({
                                    ...filters,
                                    date_from: e.target.value || undefined,
                                })
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-date-to">Hasta</Label>
                        <Input
                            id="filter-date-to"
                            type="date"
                            value={filters.date_to ?? ''}
                            onChange={(e) =>
                                applyFilters({
                                    ...filters,
                                    date_to: e.target.value || undefined,
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
                                    status: pick(option, 'todos'),
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[2.2fr_1.5fr_1.2fr_0.9fr_0.9fr_0.9fr]">
                    {[
                        'Movimiento',
                        'Artículo',
                        'Bodega',
                        'Cantidad',
                        'Costo',
                        'Saldo',
                    ].map((h, i) => (
                        <div
                            key={h}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                i >= 3 ? 'text-right' : ''
                            }`}
                        >
                            {h}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {movements.map((movement) => (
                        <div
                            key={movement.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-1 items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[2.2fr_1.5fr_1.2fr_0.9fr_0.9fr_0.9fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    inventoryMovements.show({
                                        company: companyId,
                                        id: movement.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="flex min-w-0 items-center gap-3">
                                <span
                                    className={`flex size-8 shrink-0 items-center justify-center rounded-[10px] ${
                                        movement.direction === 'in'
                                            ? 'bg-ok-soft text-ok'
                                            : 'bg-bad-soft text-bad'
                                    }`}
                                >
                                    {movement.direction === 'in' ? (
                                        <ArrowDownLeft className="size-4" />
                                    ) : (
                                        <ArrowUpRight className="size-4" />
                                    )}
                                </span>
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {MOVEMENT_TYPE_LABELS[movement.type]}
                                        {movement.status === 'reversed' && (
                                            <span className="ml-2 align-middle">
                                                <StatusPill kind="inactivo">
                                                    Revertido
                                                </StatusPill>
                                            </span>
                                        )}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {movement.code} ·{' '}
                                        {movement.movement_date ?? '—'} ·{' '}
                                        {originLabel(movement.origin_type)}
                                    </span>
                                </div>
                            </div>
                            <div className="hidden min-w-0 flex-col lg:flex">
                                <span className="truncate text-[13px] font-semibold">
                                    {movement.item_name ?? '—'}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {movement.item_code ?? '—'}
                                    {movement.lot_number
                                        ? ` · lote ${movement.lot_number}`
                                        : ''}
                                </span>
                            </div>
                            <div className="hidden min-w-0 flex-col lg:flex">
                                <span className="truncate text-[13px] font-semibold">
                                    {movement.warehouse_name ?? '—'}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {movement.location_code ??
                                        movement.location_name ??
                                        '—'}
                                </span>
                            </div>
                            <div className="hidden text-right lg:block">
                                <span
                                    className={`font-bold tabular-nums ${
                                        movement.direction === 'in'
                                            ? 'text-ok'
                                            : 'text-bad'
                                    }`}
                                >
                                    {signedQuantity(movement)}
                                </span>
                            </div>
                            <div className="hidden text-right lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {formatAmount(movement.total_cost)}
                                </span>
                            </div>
                            <div className="hidden text-right lg:block">
                                <span className="font-semibold tabular-nums">
                                    {formatQuantity(movement.balance_quantity)}
                                </span>
                            </div>
                        </div>
                    ))}
                    {movements.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Sin resultados para tu búsqueda.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {movements.length} de {meta.total} movimiento
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
