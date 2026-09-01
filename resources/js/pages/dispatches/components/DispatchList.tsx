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
import clients from '@/routes/clients';
import dispatches from '@/routes/dispatches';
import {
    DELIVERY_PILL_KIND,
    DELIVERY_STATUS_LABELS,
    isEditable,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    type Dispatch,
    type DispatchFilters,
    type DispatchMeta,
    type DispatchStatus,
} from '../types/Dispatch';

interface DispatchListProps {
    dispatches: Dispatch[];
    meta: DispatchMeta;
    filters: DispatchFilters;
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

const DELIVERY_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todas' },
    ...Object.entries(DELIVERY_STATUS_LABELS).map(([value, label]) => ({
        value,
        label,
    })),
];

export function DispatchList({
    dispatches: rows,
    meta,
    filters: initialFilters,
}: DispatchListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<DispatchFilters>(initialFilters);

    /**
     * El filtro de cliente busca contra el servidor, así que de un id que llega
     * en la URL solo sabemos su etiqueta si algún despacho listado lo nombra; el
     * resto lo resuelve la hidratación del propio select.
     */
    const client = useRemoteOption({
        url: clients.lookup(companyId).url,
        seed: initialFilters.client_id
            ? {
                  value: initialFilters.client_id,
                  label:
                      rows.find(
                          (row) => row.client_id === initialFilters.client_id,
                      )?.client_name ?? 'Cliente',
              }
            : null,
        hydrate: true,
    });

    const applyFilters = (next: DispatchFilters) => {
        setFilters(next);
        router.get(
            dispatches.index(companyId).url,
            next as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(dispatches.index(companyId).url);
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Despachos
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} despacho{meta.total !== 1 ? 's' : ''}
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() =>
                        router.visit(dispatches.create(companyId).url)
                    }
                >
                    <Plus />
                    Nuevo despacho
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
                        <Label htmlFor="filter-tracking">Guía</Label>
                        <Input
                            id="filter-tracking"
                            type="text"
                            placeholder="Número de guía..."
                            value={filters.tracking_number ?? ''}
                            onChange={(e) =>
                                setFilters({
                                    ...filters,
                                    tracking_number: e.target.value,
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
                        <Label htmlFor="filter-client">Cliente</Label>
                        <Select2Ajax
                            inputId="filter-client"
                            url={client.url}
                            value={client.optionOf(filters.client_id ?? '')}
                            onChange={(option) => {
                                client.select(option);
                                applyFilters({
                                    ...filters,
                                    client_id: option?.value ?? undefined,
                                });
                            }}
                            isClearable
                            placeholder="Todos"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-delivery">Entrega</Label>
                        <Select2
                            inputId="filter-delivery"
                            options={DELIVERY_OPTIONS}
                            value={
                                DELIVERY_OPTIONS.find(
                                    (option) =>
                                        option.value ===
                                        (filters.delivery_status ?? ALL),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    delivery_status:
                                        !option || option.value === ALL
                                            ? undefined
                                            : option.value,
                                })
                            }
                            placeholder="Entrega"
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.2fr_1fr_1.1fr_1fr_1fr_0.8fr]">
                    {[
                        'Código',
                        'Cliente',
                        'Salida',
                        'Bodega',
                        'Entrega',
                        'Estado',
                        'Acciones',
                    ].map((header, index) => (
                        <div
                            key={header}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                index === 6 ? 'text-right' : ''
                            }`}
                        >
                            {header}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {rows.map((row) => (
                        <div
                            key={row.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.2fr_1fr_1.1fr_1fr_1fr_0.8fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    dispatches.show({
                                        company: companyId,
                                        id: row.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {row.code}
                                </span>
                            </div>
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {row.client_name ?? '—'}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {row.driver_name ??
                                        row.carrier ??
                                        'Sin conductor'}
                                    {row.tracking_number
                                        ? ` · ${row.tracking_number}`
                                        : ''}
                                </span>
                            </div>
                            <div className="hidden text-[13.5px] font-medium text-muted-foreground tabular-nums lg:block">
                                {row.dispatch_date}
                            </div>
                            <div className="hidden truncate text-[13.5px] lg:block">
                                {row.warehouse_name ?? '—'}
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={
                                        DELIVERY_PILL_KIND[row.delivery_status]
                                    }
                                >
                                    {
                                        DELIVERY_STATUS_LABELS[
                                            row.delivery_status
                                        ]
                                    }
                                </StatusPill>
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill kind={STATUS_PILL_KIND[row.status]}>
                                    {STATUS_LABELS[row.status]}
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
                                                    dispatches.show({
                                                        company: companyId,
                                                        id: row.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </DropdownMenuItem>
                                        {isEditable(
                                            row.status as DispatchStatus,
                                        ) && (
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    router.visit(
                                                        dispatches.edit({
                                                            company: companyId,
                                                            id: row.id,
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
                    {rows.length} de {meta.total} despacho
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
