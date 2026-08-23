import { router, usePage } from '@inertiajs/react';
import { Eye, MoreHorizontal, Power, Search } from 'lucide-react';
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
import itemStocks from '@/routes/item-stocks';
import {
    formatAmount,
    formatQuantity,
    type ItemStock,
    type ItemStockFilters,
    type ItemStockMeta,
    type WarehouseOption,
} from '../types/ItemStock';

interface ItemStockListProps {
    stocks: ItemStock[];
    warehouses: WarehouseOption[];
    meta: ItemStockMeta;
    filters: ItemStockFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const STATUS_OPTIONS: OptionType[] = [
    { value: 'todos', label: 'Todos' },
    { value: 'active', label: 'Activos' },
    { value: 'inactive', label: 'Retirados' },
];

const STOCK_OPTIONS: OptionType[] = [
    { value: 'todos', label: 'Todos los saldos' },
    { value: 'yes', label: 'Solo con existencia' },
];

export function ItemStockList({
    stocks,
    warehouses,
    meta,
    filters: initialFilters,
}: ItemStockListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<ItemStockFilters>(initialFilters);

    const warehouseOptions: OptionType[] = [
        { value: 'todas', label: 'Todas' },
        ...warehouses.map((warehouse) => ({
            value: warehouse.id,
            label: warehouse.name,
        })),
    ];

    const applyFilters = (next: ItemStockFilters) => {
        setFilters(next);
        router.get(
            itemStocks.index(companyId).url,
            next as Record<string, string>,
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(itemStocks.index(companyId).url);
    };

    const handleToggleStatus = (stock: ItemStock) => {
        router.put(
            itemStocks.updateStatus({ company: companyId, id: stock.id }).url,
            { status: stock.status === 'active' ? 'inactive' : 'active' },
        );
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Existencias
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} saldo{meta.total !== 1 ? 's' : ''} · el
                        inventario lo mueven los documentos, no esta pantalla
                    </p>
                </div>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2">
                        <Label htmlFor="filter-q">Artículo</Label>
                        <Input
                            id="filter-q"
                            type="text"
                            placeholder="Nombre, código o SKU..."
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
                                    warehouse_id:
                                        !option || option.value === 'todas'
                                            ? undefined
                                            : option.value,
                                })
                            }
                            placeholder="Bodega"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-with-stock">Existencia</Label>
                        <Select2
                            inputId="filter-with-stock"
                            options={STOCK_OPTIONS}
                            value={
                                STOCK_OPTIONS.find(
                                    (option) =>
                                        option.value ===
                                        (filters.with_stock ?? 'todos'),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    with_stock:
                                        !option || option.value === 'todos'
                                            ? undefined
                                            : option.value,
                                })
                            }
                            placeholder="Existencia"
                            isSearchable={false}
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[2.2fr_1.6fr_0.9fr_0.9fr_0.9fr_0.7fr]">
                    {[
                        'Artículo',
                        'Ubicación',
                        'Existencia',
                        'Disponible',
                        'Valor',
                        'Acciones',
                    ].map((h, i) => (
                        <div
                            key={h}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                i >= 2 ? 'text-right' : ''
                            }`}
                        >
                            {h}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {stocks.map((stock) => (
                        <div
                            key={stock.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[2.2fr_1.6fr_0.9fr_0.9fr_0.9fr_0.7fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    itemStocks.show({
                                        company: companyId,
                                        id: stock.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {stock.item_name ?? '—'}
                                    {stock.status === 'inactive' && (
                                        <span className="ml-2 align-middle">
                                            <StatusPill kind="inactivo" />
                                        </span>
                                    )}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {stock.item_code ?? '—'}
                                    {stock.lot_number
                                        ? ` · lote ${stock.lot_number}`
                                        : ''}
                                </span>
                            </div>
                            <div className="hidden min-w-0 flex-col lg:flex">
                                <span className="truncate text-[13px] font-semibold">
                                    {stock.warehouse_name ?? '—'}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {stock.location_code ??
                                        stock.location_name ??
                                        '—'}
                                </span>
                            </div>
                            <div className="hidden text-right lg:block">
                                <span className="font-bold tabular-nums">
                                    {formatQuantity(stock.quantity)}
                                </span>
                            </div>
                            <div className="hidden text-right lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {formatQuantity(stock.available_quantity)}
                                </span>
                            </div>
                            <div className="hidden text-right lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {formatAmount(stock.total_value)}
                                </span>
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
                                                    itemStocks.show({
                                                        company: companyId,
                                                        id: stock.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            onClick={() =>
                                                handleToggleStatus(stock)
                                            }
                                        >
                                            <Power className="mr-2 h-4 w-4" />
                                            {stock.status === 'active'
                                                ? 'Retirar saldo'
                                                : 'Reactivar'}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    ))}
                    {stocks.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Sin resultados para tu búsqueda.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {stocks.length} de {meta.total} saldo
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
