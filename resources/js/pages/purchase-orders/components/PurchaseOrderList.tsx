import { router, usePage } from '@inertiajs/react';
import { Edit, Eye, MoreHorizontal, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import { Select2Ajax } from '@/components/select2-ajax';
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
import { useRemoteOption } from '@/hooks/use-remote-option';
import purchaseOrders from '@/routes/purchase-orders';
import suppliers from '@/routes/suppliers';
import {
    isEditable,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    type PurchaseOrder,
    type PurchaseOrderFilters,
    type PurchaseOrderMeta,
    type PurchaseOrderOptions,
    type PurchaseOrderStatus,
} from '../types/PurchaseOrder';

interface PurchaseOrderListProps {
    purchaseOrders: PurchaseOrder[];
    meta: PurchaseOrderMeta;
    filters: PurchaseOrderFilters;
    options: PurchaseOrderOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const ALL = 'todos';

const STATUS_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todos' },
    ...Object.entries(STATUS_LABELS).map(([value, label]) => ({
        value,
        label,
    })),
];

export function PurchaseOrderList({
    purchaseOrders: rows,
    meta,
    filters: initialFilters,
    options,
}: PurchaseOrderListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] =
        useState<PurchaseOrderFilters>(initialFilters);

    /**
     * El filtro de proveedor busca contra el servidor, así que de un id que
     * llega en la URL solo sabemos su etiqueta si alguna orden listada lo
     * nombra; el resto lo resuelve la hidratación del propio select.
     */
    const supplier = useRemoteOption({
        url: suppliers.lookup(companyId).url,
        seed: initialFilters.supplier_id
            ? {
                  value: initialFilters.supplier_id,
                  label:
                      rows.find(
                          (order) =>
                              order.supplier_id === initialFilters.supplier_id,
                      )?.supplier_name ?? 'Proveedor',
              }
            : null,
        hydrate: true,
    });

    const warehouseOptions: OptionType[] = [
        { value: ALL, label: 'Todas' },
        ...options.warehouses.map((warehouse) => ({
            value: warehouse.id,
            label: warehouse.name,
        })),
    ];

    const applyFilters = (next: PurchaseOrderFilters) => {
        setFilters(next);
        router.get(
            purchaseOrders.index(companyId).url,
            next as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(purchaseOrders.index(companyId).url);
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Órdenes de compra
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} orden{meta.total !== 1 ? 'es' : ''}
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() =>
                        router.visit(purchaseOrders.create(companyId).url)
                    }
                >
                    <Plus />
                    Nueva orden
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2">
                        <Label htmlFor="filter-code">Código</Label>
                        <Input
                            id="filter-code"
                            type="text"
                            placeholder="Buscar por código..."
                            value={filters.code ?? ''}
                            onChange={(e) =>
                                setFilters({ ...filters, code: e.target.value })
                            }
                            onKeyDown={(e) =>
                                e.key === 'Enter' && applyFilters(filters)
                            }
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-reference">Referencia</Label>
                        <Input
                            id="filter-reference"
                            type="text"
                            placeholder="Buscar por referencia..."
                            value={filters.supplier_reference ?? ''}
                            onChange={(e) =>
                                setFilters({
                                    ...filters,
                                    supplier_reference: e.target.value,
                                })
                            }
                            onKeyDown={(e) =>
                                e.key === 'Enter' && applyFilters(filters)
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
                        <Label htmlFor="filter-supplier">Proveedor</Label>
                        <Select2Ajax
                            inputId="filter-supplier"
                            url={supplier.url}
                            value={supplier.optionOf(filters.supplier_id ?? '')}
                            onChange={(option) => {
                                supplier.select(option);
                                applyFilters({
                                    ...filters,
                                    supplier_id: option?.value ?? undefined,
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
                                        (filters.warehouse_id ?? ALL),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    warehouse_id:
                                        !option || option.value === ALL
                                            ? undefined
                                            : option.value,
                                })
                            }
                            placeholder="Bodega"
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.4fr_1fr_1.2fr_1fr_0.8fr]">
                    {[
                        'Código',
                        'Proveedor',
                        'Emisión',
                        'Total',
                        'Estado',
                        'Acciones',
                    ].map((header, index) => (
                        <div
                            key={header}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                index === 5 ? 'text-right' : ''
                            }`}
                        >
                            {header}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {rows.map((order) => (
                        <div
                            key={order.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.4fr_1fr_1.2fr_1fr_0.8fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    purchaseOrders.show({
                                        company: companyId,
                                        id: order.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {order.code}
                                </span>
                            </div>
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {order.supplier_name ?? '—'}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {order.warehouse_name ??
                                        order.supplier_reference ??
                                        '—'}
                                </span>
                            </div>
                            <div className="hidden text-[13.5px] font-medium text-muted-foreground tabular-nums lg:block">
                                {order.order_date}
                            </div>
                            <div className="hidden text-[13.5px] font-semibold tabular-nums lg:block">
                                {order.currency} {order.total}
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={STATUS_PILL_KIND[order.status]}
                                >
                                    {STATUS_LABELS[order.status]}
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
                                                    purchaseOrders.show({
                                                        company: companyId,
                                                        id: order.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </DropdownMenuItem>
                                        {isEditable(
                                            order.status as PurchaseOrderStatus,
                                        ) && (
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    router.visit(
                                                        purchaseOrders.edit({
                                                            company: companyId,
                                                            id: order.id,
                                                        }).url,
                                                    )
                                                }
                                            >
                                                <Edit className="mr-2 h-4 w-4" />
                                                Editar
                                            </DropdownMenuItem>
                                        )}
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
                    {rows.length} de {meta.total} orden
                    {meta.total !== 1 ? 'es' : ''}
                </div>
            </Card>
        </div>
    );
}
