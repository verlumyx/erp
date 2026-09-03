import { router, usePage } from '@inertiajs/react';
import { Eye, MoreHorizontal, Search } from 'lucide-react';
import { useState } from 'react';
import { AmountDual } from '@/components/amount-dual';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import storeOrders from '@/routes/store-orders';
import {
    ORDER_STATUS_LABELS,
    ORDER_STATUS_PILL,
    type ListMeta,
    type StoreOrder,
    type StoreOrderFilters,
} from '../types/Store';

interface StoreOrderListProps {
    storeOrders: StoreOrder[];
    meta: ListMeta;
    filters: StoreOrderFilters;
    pendingCount?: number;
    /** En la ficha del comprador la lista va sin cabecera ni filtros. */
    embedded?: boolean;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const ALL = 'todos';

const STATUS_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todos' },
    ...Object.entries(ORDER_STATUS_LABELS).map(([value, label]) => ({
        value,
        label,
    })),
];

export function StoreOrderList({
    storeOrders: rows,
    meta,
    filters: initialFilters,
    pendingCount,
    embedded = false,
}: StoreOrderListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<StoreOrderFilters>(initialFilters);

    const applyFilters = (next: StoreOrderFilters) => {
        setFilters(next);
        router.get(
            storeOrders.index(companyId).url,
            next as Record<string, string>,
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(storeOrders.index(companyId).url);
    };

    const goTo = (row: StoreOrder) =>
        router.visit(storeOrders.show({ company: companyId, id: row.id }).url);

    return (
        <div className="flex flex-col gap-5">
            {!embedded && (
                <>
                    <div>
                        <h1 className="text-[27px] font-extrabold tracking-tight">
                            Pedidos web
                        </h1>
                        <p className="mt-1 text-[14.5px] text-muted-foreground">
                            {meta.total} pedido{meta.total !== 1 ? 's' : ''}
                            {pendingCount !== undefined
                                ? ` · ${pendingCount} pendiente${pendingCount !== 1 ? 's' : ''} de revisar`
                                : ''}
                        </p>
                    </div>

                    <div className="rounded-lg border bg-card p-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="space-y-2">
                                <Label htmlFor="filter-q">Buscar</Label>
                                <Input
                                    id="filter-q"
                                    placeholder="Código, comprador o correo…"
                                    value={filters.q ?? ''}
                                    onChange={(e) =>
                                        setFilters({
                                            ...filters,
                                            q: e.target.value,
                                        })
                                    }
                                    onKeyDown={(e) =>
                                        e.key === 'Enter' &&
                                        applyFilters(filters)
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="filter-date-from">Desde</Label>
                                <Input
                                    id="filter-date-from"
                                    type="date"
                                    value={filters.date_from ?? ''}
                                    onChange={(e) =>
                                        setFilters({
                                            ...filters,
                                            date_from: e.target.value,
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
                                        setFilters({
                                            ...filters,
                                            date_to: e.target.value,
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
                </>
            )}

            <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.2fr_1.2fr_1.3fr_1fr_1.2fr_0.7fr]">
                    {[
                        'Código',
                        'Comprador',
                        'Fecha',
                        'Total',
                        'Estado',
                        'Orden de venta',
                        'Acciones',
                    ].map((header, index) => (
                        <div
                            key={header}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${index === 6 ? 'text-right' : ''}`}
                        >
                            {header}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {rows.map((row) => (
                        <div
                            key={row.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.2fr_1.2fr_1.3fr_1fr_1.2fr_0.7fr] lg:py-0"
                            onClick={() => goTo(row)}
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {row.code}
                                </span>
                            </div>
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {row.buyer_name}
                                    {row.needs_review && (
                                        <span className="ml-2 rounded-full bg-warn-soft px-2 py-0.5 text-[11.5px] font-bold text-warn">
                                            Revisar
                                        </span>
                                    )}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {row.buyer_email}
                                    {row.client
                                        ? ` · ${row.client.code}`
                                        : ' · Sin cliente'}
                                </span>
                            </div>
                            <div className="hidden text-[13.5px] font-medium text-muted-foreground tabular-nums lg:block">
                                {row.created_at ?? '—'}
                            </div>
                            <div className="hidden text-[13.5px] font-semibold tabular-nums lg:block">
                                <AmountDual
                                    amount={row.total}
                                    currency={row.currency}
                                    rate={row.exchange_rate}
                                />
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={ORDER_STATUS_PILL[row.status]}
                                >
                                    {ORDER_STATUS_LABELS[row.status]}
                                </StatusPill>
                            </div>
                            <div className="hidden text-[13.5px] tabular-nums lg:block">
                                {row.sales_order?.code ?? '—'}
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
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    ))}
                    {rows.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            {embedded
                                ? 'Este comprador todavía no ha hecho pedidos.'
                                : 'No hay pedidos web para tu búsqueda.'}
                        </div>
                    )}
                </div>
                {!embedded && (
                    <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                        {rows.length} de {meta.total} pedido
                        {meta.total !== 1 ? 's' : ''}
                    </div>
                )}
            </Card>
        </div>
    );
}
