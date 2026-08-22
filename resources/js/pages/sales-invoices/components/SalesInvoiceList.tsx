import { router, usePage } from '@inertiajs/react';
import { Edit, Eye, MoreHorizontal, Plus, Search } from 'lucide-react';
import { useState } from 'react';
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
import salesInvoices from '@/routes/sales-invoices';
import {
    formatAmount,
    isEditable,
    PAYMENT_STATUS_LABELS,
    STATUS_LABELS,
    type PaymentStatus,
    type SalesInvoice,
    type SalesInvoiceFilters,
    type SalesInvoiceMeta,
    type SalesInvoiceOptions,
    type SalesInvoiceStatus,
} from '../types/SalesInvoice';
import {
    SalesInvoicePaymentPill,
    SalesInvoiceStatusPill,
} from './SalesInvoiceStatusPill';

interface SalesInvoiceListProps {
    salesInvoices: SalesInvoice[];
    meta: SalesInvoiceMeta;
    filters: SalesInvoiceFilters;
    options: SalesInvoiceOptions;
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

const PAYMENT_STATUS_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todos' },
    ...Object.entries(PAYMENT_STATUS_LABELS).map(([value, label]) => ({
        value,
        label,
    })),
];

export function SalesInvoiceList({
    salesInvoices: rows,
    meta,
    filters: initialFilters,
    options,
}: SalesInvoiceListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<SalesInvoiceFilters>(initialFilters);

    const warehouseOptions: OptionType[] = [
        { value: ALL, label: 'Todas' },
        ...options.warehouses.map((warehouse) => ({
            value: warehouse.id,
            label: warehouse.name,
        })),
    ];

    const applyFilters = (next: SalesInvoiceFilters) => {
        setFilters(next);
        router.get(
            salesInvoices.index(companyId).url,
            next as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(salesInvoices.index(companyId).url);
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Facturas de venta
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} factura{meta.total !== 1 ? 's' : ''}
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() =>
                        router.visit(salesInvoices.create(companyId).url)
                    }
                >
                    <Plus />
                    Nueva factura
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {(
                        [
                            ['code', 'Código'],
                            ['client', 'Cliente'],
                            ['invoice_number', 'Número fiscal'],
                        ] as Array<[keyof SalesInvoiceFilters, string]>
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
                        <Label htmlFor="filter-date-from">Desde</Label>
                        <Input
                            id="filter-date-from"
                            type="date"
                            value={filters.invoice_date_from ?? ''}
                            onChange={(e) =>
                                setFilters({
                                    ...filters,
                                    invoice_date_from: e.target.value,
                                })
                            }
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-date-to">Hasta</Label>
                        <Input
                            id="filter-date-to"
                            type="date"
                            value={filters.invoice_date_to ?? ''}
                            onChange={(e) =>
                                setFilters({
                                    ...filters,
                                    invoice_date_to: e.target.value,
                                })
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

                    <div className="space-y-2">
                        <Label htmlFor="filter-payment-status">Cobro</Label>
                        <Select2
                            inputId="filter-payment-status"
                            options={PAYMENT_STATUS_OPTIONS}
                            value={
                                PAYMENT_STATUS_OPTIONS.find(
                                    (option) =>
                                        option.value ===
                                        (filters.payment_status ?? ALL),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    payment_status:
                                        !option || option.value === ALL
                                            ? undefined
                                            : option.value,
                                })
                            }
                            placeholder="Cobro"
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2fr_0.9fr_1.1fr_0.9fr_0.9fr_0.8fr]">
                    {[
                        'Código',
                        'Cliente',
                        'Vence',
                        'Total',
                        'Estado',
                        'Cobro',
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
                    {rows.map((invoice) => (
                        <div
                            key={invoice.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2fr_0.9fr_1.1fr_0.9fr_0.9fr_0.8fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    salesInvoices.show({
                                        company: companyId,
                                        id: invoice.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden min-w-0 flex-col lg:flex">
                                <span className="truncate font-semibold text-muted-foreground tabular-nums">
                                    {invoice.code}
                                </span>
                                {invoice.invoice_number && (
                                    <span className="truncate text-[12.5px] text-muted-foreground tabular-nums">
                                        {invoice.invoice_series
                                            ? `${invoice.invoice_series}-`
                                            : ''}
                                        {invoice.invoice_number}
                                    </span>
                                )}
                            </div>
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {invoice.client_name ?? '—'}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {invoice.invoice_date}
                                    {invoice.warehouse_name
                                        ? ` · ${invoice.warehouse_name}`
                                        : ''}
                                </span>
                            </div>
                            <div className="hidden text-[13.5px] font-medium text-muted-foreground tabular-nums lg:block">
                                {invoice.due_date}
                            </div>
                            <div className="hidden flex-col lg:flex">
                                <span className="text-[13.5px] font-semibold tabular-nums">
                                    {formatAmount(
                                        invoice.total,
                                        invoice.currency,
                                    )}
                                </span>
                                {Number(invoice.balance) !==
                                    Number(invoice.total) && (
                                    <span className="text-[12.5px] text-muted-foreground tabular-nums">
                                        Saldo{' '}
                                        {formatAmount(
                                            invoice.balance,
                                            invoice.currency,
                                        )}
                                    </span>
                                )}
                            </div>
                            <div className="hidden lg:block">
                                <SalesInvoiceStatusPill
                                    status={
                                        invoice.status as SalesInvoiceStatus
                                    }
                                />
                            </div>
                            <div className="hidden lg:block">
                                <SalesInvoicePaymentPill
                                    status={
                                        invoice.payment_status as PaymentStatus
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
                                                    salesInvoices.show({
                                                        company: companyId,
                                                        id: invoice.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </DropdownMenuItem>
                                        {isEditable(invoice) && (
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    router.visit(
                                                        salesInvoices.edit({
                                                            company: companyId,
                                                            id: invoice.id,
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
                    {rows.length} de {meta.total} factura
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
