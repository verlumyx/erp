import { router, usePage } from '@inertiajs/react';
import {
    Check,
    Eye,
    MoreHorizontal,
    Pencil,
    Plus,
    Search,
    Undo2,
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { clp } from '@/lib/crm-demo';
import refunds from '@/routes/refunds';
import {
    REFUND_STATUS_LABELS,
    REFUND_STATUSES,
    refundStatusPill,
    type Refund,
    type RefundFilters,
    type RefundMeta,
} from '../types/Refund';

interface RefundListProps {
    refunds: Refund[];
    meta: RefundMeta;
    filters: RefundFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    auth?: { permissions?: string[] };
    [key: string]: unknown;
}

export function RefundList({
    refunds: items,
    meta,
    filters: initialFilters,
}: RefundListProps) {
    const { currentCompany, auth } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const can = (p: string) => auth?.permissions?.includes(p) ?? false;

    const [search, setSearch] = useState(initialFilters.q ?? '');
    const [statusFilter, setStatusFilter] = useState(
        initialFilters.status ?? 'all',
    );

    const handleSearch = () => {
        const filters: Record<string, string> = {};
        if (search.trim()) filters.q = search.trim();
        if (statusFilter !== 'all') filters.status = statusFilter;

        router.get(refunds.index(companyId).url, filters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleClear = () => {
        setSearch('');
        setStatusFilter('all');
        router.get(refunds.index(companyId).url);
    };

    const resolve = (refund: Refund, action: 'approve' | 'reject') => {
        const url =
            action === 'approve'
                ? refunds.approve({ company: companyId, id: refund.id }).url
                : refunds.reject({ company: companyId, id: refund.id }).url;
        router.post(url, {}, { preserveScroll: true });
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Reembolsos
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} reembolso{meta.total !== 1 ? 's' : ''}{' '}
                        registrado{meta.total !== 1 ? 's' : ''}
                    </p>
                </div>
                {can('refunds.create') && (
                    <Button
                        className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                        onClick={() =>
                            router.visit(refunds.create(companyId).url)
                        }
                    >
                        <Plus />
                        Nuevo reembolso
                    </Button>
                )}
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="search">Buscar</Label>
                        <Input
                            id="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) =>
                                e.key === 'Enter' && handleSearch()
                            }
                            placeholder="Código o razón..."
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="status-filter">Estado</Label>
                        <Select
                            value={statusFilter}
                            onValueChange={setStatusFilter}
                        >
                            <SelectTrigger id="status-filter">
                                <SelectValue placeholder="Todos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                {REFUND_STATUSES.map((s) => (
                                    <SelectItem key={s} value={s}>
                                        {REFUND_STATUS_LABELS[s]}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2fr_1fr_1fr_1.1fr_0.7fr]">
                    {[
                        'Código',
                        'Cliente',
                        'Venta',
                        'Monto',
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
                    {items.map((refund) => (
                        <div
                            key={refund.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2fr_1fr_1fr_1.1fr_0.7fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    refunds.show({
                                        company: companyId,
                                        id: refund.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {refund.code}
                                </span>
                            </div>
                            <div className="flex items-center gap-3">
                                <span className="grid size-10 shrink-0 place-items-center rounded-[11px] border bg-muted text-muted-foreground">
                                    <Undo2 className="size-5" />
                                </span>
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {refund.client?.name ?? '—'}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {refund.reason ?? '—'}
                                    </span>
                                </div>
                            </div>
                            <div className="hidden lg:block">
                                <span className="text-[13.5px] font-semibold tabular-nums">
                                    {refund.sale?.code ?? '—'}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <span className="font-bold tabular-nums">
                                    {clp(Number(refund.amount))}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={refundStatusPill(refund.status)}
                                >
                                    {REFUND_STATUS_LABELS[refund.status]}
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
                                                    refunds.show({
                                                        company: companyId,
                                                        id: refund.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </DropdownMenuItem>
                                        {refund.is_pending &&
                                            can('refunds.update') && (
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        router.visit(
                                                            refunds.show({
                                                                company:
                                                                    companyId,
                                                                id: refund.id,
                                                            }).url,
                                                        )
                                                    }
                                                >
                                                    <Pencil className="mr-2 h-4 w-4" />
                                                    Editar
                                                </DropdownMenuItem>
                                            )}
                                        {refund.is_pending &&
                                            can('refunds.approve') && (
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        resolve(
                                                            refund,
                                                            'approve',
                                                        )
                                                    }
                                                >
                                                    <Check className="mr-2 h-4 w-4" />
                                                    Aprobar
                                                </DropdownMenuItem>
                                            )}
                                        {refund.is_pending &&
                                            can('refunds.reject') && (
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        resolve(
                                                            refund,
                                                            'reject',
                                                        )
                                                    }
                                                >
                                                    <X className="mr-2 h-4 w-4" />
                                                    Rechazar
                                                </DropdownMenuItem>
                                            )}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    ))}
                    {items.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Sin resultados para tu búsqueda.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {items.length} de {meta.total} reembolso
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
