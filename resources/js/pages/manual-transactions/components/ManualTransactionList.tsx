import { router, usePage } from '@inertiajs/react';
import {
    Check,
    Eye,
    MoreHorizontal,
    NotebookPen,
    Plus,
    Search,
    X,
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
import manualTransactions from '@/routes/manual-transactions';
import {
    MANUAL_TRANSACTION_STATUS_LABELS,
    manualTransactionStatusPill,
    type ManualTransaction,
    type ManualTransactionFilters,
    type ManualTransactionMeta,
} from '../types/ManualTransaction';

interface ManualTransactionListProps {
    manualTransactions: ManualTransaction[];
    meta: ManualTransactionMeta;
    filters: ManualTransactionFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    auth?: { permissions?: string[] };
    [key: string]: unknown;
}

function formatTotal(total: string, currency: string): string {
    const value = Number(total);
    if (Number.isNaN(value)) {
        return `${total} ${currency}`;
    }
    return `${value.toLocaleString('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} ${currency}`;
}

export function ManualTransactionList({
    manualTransactions: items,
    meta,
    filters: initialFilters,
}: ManualTransactionListProps) {
    const { currentCompany, auth } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const can = (p: string) => auth?.permissions?.includes(p) ?? false;

    const [filters, setFilters] =
        useState<ManualTransactionFilters>(initialFilters);

    const handleSearch = () => {
        router.get(manualTransactions.index(companyId).url, { ...filters }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleClear = () => {
        setFilters({});
        router.get(manualTransactions.index(companyId).url);
    };

    const goToShow = (id: string) =>
        router.visit(manualTransactions.show({ company: companyId, id }).url);

    const resolve = (id: string, action: 'approve' | 'cancel') => {
        const url =
            action === 'approve'
                ? manualTransactions.approve({ company: companyId, id }).url
                : manualTransactions.cancel({ company: companyId, id }).url;
        router.post(url, {}, { preserveScroll: true });
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Transacciones manuales
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} transacción{meta.total !== 1 ? 'es' : ''}{' '}
                        registrada{meta.total !== 1 ? 's' : ''}
                    </p>
                </div>
                {can('manual-transactions.create') && (
                    <Button
                        className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                        onClick={() =>
                            router.visit(
                                manualTransactions.create(companyId).url,
                            )
                        }
                    >
                        <Plus />
                        Nueva transacción
                    </Button>
                )}
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div className="space-y-2">
                        <Label htmlFor="filter-code">Código</Label>
                        <Input
                            id="filter-code"
                            value={filters.code ?? ''}
                            onChange={(e) =>
                                setFilters({ ...filters, code: e.target.value })
                            }
                            onKeyDown={(e) =>
                                e.key === 'Enter' && handleSearch()
                            }
                            placeholder="MTX..."
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
                </div>
                <div className="mt-4 flex justify-end gap-2">
                    <Button onClick={handleSearch} variant="default">
                        <Search className="mr-2 h-4 w-4" />
                        Buscar
                    </Button>
                    <Button onClick={handleClear} variant="outline">
                        Limpiar
                    </Button>
                </div>
            </div>

            <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[1fr_1fr_1.1fr_1.7fr_0.7fr_1fr_0.7fr]">
                    {[
                        'Código',
                        'Fecha',
                        'Estado',
                        'Descripción',
                        'Líneas',
                        'Total',
                        'Acciones',
                    ].map((h, i) => (
                        <div
                            key={h}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                i === 5 || i === 6 ? 'text-right' : ''
                            }`}
                        >
                            {h}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {items.map((item) => (
                        <div
                            key={item.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[1fr_1fr_1.1fr_1.7fr_0.7fr_1fr_0.7fr] lg:py-0"
                            onClick={() => goToShow(item.id)}
                        >
                            <div className="flex items-center gap-3 lg:block">
                                <span className="grid size-10 shrink-0 place-items-center rounded-[11px] border bg-muted text-muted-foreground lg:hidden">
                                    <NotebookPen className="size-5" />
                                </span>
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {item.code}
                                </span>
                            </div>
                            <div className="hidden tabular-nums lg:block">
                                {item.date ?? '—'}
                            </div>
                            <div>
                                <StatusPill
                                    kind={manualTransactionStatusPill(
                                        item.status,
                                    )}
                                >
                                    {MANUAL_TRANSACTION_STATUS_LABELS[
                                        item.status
                                    ]}
                                </StatusPill>
                            </div>
                            <div className="hidden truncate text-muted-foreground lg:block">
                                {item.description ?? '—'}
                            </div>
                            <div className="hidden tabular-nums lg:block">
                                {item.lines?.length ?? 0}
                            </div>
                            <div className="text-right font-bold tabular-nums">
                                {formatTotal(item.total, item.currency)}
                            </div>
                            <div
                                className="flex items-center justify-end"
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
                                            onClick={() => goToShow(item.id)}
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </DropdownMenuItem>
                                        {item.can_be_approved &&
                                            can('manual-transactions.approve') && (
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        resolve(
                                                            item.id,
                                                            'approve',
                                                        )
                                                    }
                                                >
                                                    <Check className="mr-2 h-4 w-4" />
                                                    Aprobar
                                                </DropdownMenuItem>
                                            )}
                                        {item.can_be_cancelled &&
                                            can('manual-transactions.cancel') && (
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        resolve(
                                                            item.id,
                                                            'cancel',
                                                        )
                                                    }
                                                >
                                                    <X className="mr-2 h-4 w-4" />
                                                    Cancelar
                                                </DropdownMenuItem>
                                            )}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    ))}
                    {items.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Sin transacciones manuales registradas.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {items.length} de {meta.total} transacción
                    {meta.total !== 1 ? 'es' : ''}
                </div>
            </Card>
        </div>
    );
}
