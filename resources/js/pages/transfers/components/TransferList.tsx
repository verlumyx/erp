import { router, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Edit,
    Eye,
    MoreHorizontal,
    Plus,
    Search,
} from 'lucide-react';
import { useState } from 'react';
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
import transfers from '@/routes/transfers';
import {
    isEditable,
    MOVEMENT_PILL_KIND,
    MOVEMENT_STATUS_LABELS,
    REASON_LABELS,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    type Transfer,
    type TransferFilters,
    type TransferMeta,
    type TransferOptions,
} from '../types/Transfer';

interface TransferListProps {
    transfers: Transfer[];
    meta: TransferMeta;
    filters: TransferFilters;
    options: TransferOptions;
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

const MOVEMENT_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todos' },
    ...Object.entries(MOVEMENT_STATUS_LABELS).map(([value, label]) => ({
        value,
        label,
    })),
];

const REASON_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todos' },
    ...Object.entries(REASON_LABELS).map(([value, label]) => ({
        value,
        label,
    })),
];

export function TransferList({
    transfers: rows,
    meta,
    filters: initialFilters,
    options,
}: TransferListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<TransferFilters>(initialFilters);

    /** Una sola bodega responde la pregunta del bodeguero: qué se mueve por aquí. */
    const warehouseOptions: OptionType[] = [
        { value: ALL, label: 'Todas' },
        ...options.warehouses.map((warehouse) => ({
            value: warehouse.id,
            label: warehouse.name,
        })),
    ];

    const applyFilters = (next: TransferFilters) => {
        setFilters(next);
        router.get(
            transfers.index(companyId).url,
            next as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(transfers.index(companyId).url);
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Traslados
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} traslado{meta.total !== 1 ? 's' : ''}
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() =>
                        router.visit(transfers.create(companyId).url)
                    }
                >
                    <Plus />
                    Nuevo traslado
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
                            placeholder="Todas"
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
                        <Label htmlFor="filter-reason">Motivo</Label>
                        <Select2
                            inputId="filter-reason"
                            options={REASON_OPTIONS}
                            value={
                                REASON_OPTIONS.find(
                                    (option) =>
                                        option.value ===
                                        (filters.reason ?? ALL),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    reason:
                                        !option || option.value === ALL
                                            ? undefined
                                            : option.value,
                                })
                            }
                            placeholder="Motivo"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-movement">Mercancía</Label>
                        <Select2
                            inputId="filter-movement"
                            options={MOVEMENT_OPTIONS}
                            value={
                                MOVEMENT_OPTIONS.find(
                                    (option) =>
                                        option.value ===
                                        (filters.transfer_status ?? ALL),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    transfer_status:
                                        !option || option.value === ALL
                                            ? undefined
                                            : option.value,
                                })
                            }
                            placeholder="Dónde está"
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.4fr_1fr_1fr_1fr_0.8fr]">
                    {[
                        'Código',
                        'Ruta',
                        'Salida',
                        'Mercancía',
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
                    {rows.map((row) => (
                        <div
                            key={row.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.4fr_1fr_1fr_1fr_0.8fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    transfers.show({
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
                                <span className="flex min-w-0 items-center gap-1.5 truncate font-bold">
                                    {row.origin_warehouse_name ?? '—'}
                                    <ArrowRight className="size-3.5 shrink-0 opacity-60" />
                                    {row.destination_warehouse_name ?? '—'}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {REASON_LABELS[row.reason]}
                                    {row.driver_name
                                        ? ` · ${row.driver_name}`
                                        : ''}
                                </span>
                            </div>
                            <div className="hidden text-[13.5px] font-medium text-muted-foreground tabular-nums lg:block">
                                {row.transfer_date}
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={
                                        MOVEMENT_PILL_KIND[row.transfer_status]
                                    }
                                >
                                    {
                                        MOVEMENT_STATUS_LABELS[
                                            row.transfer_status
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
                                                    transfers.show({
                                                        company: companyId,
                                                        id: row.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </DropdownMenuItem>
                                        {isEditable(row.status) && (
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    router.visit(
                                                        transfers.edit({
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
                    {rows.length} de {meta.total} traslado
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
