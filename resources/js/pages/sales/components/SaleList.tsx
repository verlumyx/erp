import { router, usePage } from '@inertiajs/react';
import {
    Ban,
    Eye,
    MoreHorizontal,
    Plus,
    RefreshCw,
    RotateCcw,
    Search,
    ShoppingCart,
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
import sales from '@/routes/sales';
import {
    SALE_STATUS_LABELS,
    SALE_STATUSES,
    saleStatusPill,
    type AgentOption,
    type ClientOption,
    type Sale,
    type SaleFilters,
    type SaleMeta,
    type ServiceOption,
} from '../types/Sale';
import { SaleCancelDialog } from './SaleCancelDialog';
import { SaleRenewDialog } from './SaleRenewDialog';

interface SaleListProps {
    sales: Sale[];
    meta: SaleMeta;
    filters: SaleFilters;
    clients: ClientOption[];
    services: ServiceOption[];
    agents: AgentOption[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    auth?: { permissions?: string[] };
    [key: string]: unknown;
}

export function SaleList({
    sales: items,
    meta,
    filters: initialFilters,
    clients,
    services,
    agents,
}: SaleListProps) {
    const { currentCompany, auth } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const can = (p: string) => auth?.permissions?.includes(p) ?? false;

    const [searchCode, setSearchCode] = useState(initialFilters.code ?? '');
    const [statusFilter, setStatusFilter] = useState(
        initialFilters.status ?? 'all',
    );
    const [clientFilter, setClientFilter] = useState(
        initialFilters.client_id ?? 'all',
    );
    const [serviceFilter, setServiceFilter] = useState(
        initialFilters.service_id ?? 'all',
    );
    const [agentFilter, setAgentFilter] = useState(
        initialFilters.agent_id ?? 'all',
    );
    const [expiringSoon, setExpiringSoon] = useState(
        Boolean(initialFilters.expiring_soon),
    );

    const [renewSale, setRenewSale] = useState<Sale | null>(null);
    const [cancelSale, setCancelSale] = useState<Sale | null>(null);

    const handleSearch = () => {
        const filters: Record<string, string> = {};
        if (searchCode.trim()) filters.code = searchCode.trim();
        if (statusFilter !== 'all') filters.status = statusFilter;
        if (clientFilter !== 'all') filters.client_id = clientFilter;
        if (serviceFilter !== 'all') filters.service_id = serviceFilter;
        if (agentFilter !== 'all') filters.agent_id = agentFilter;
        if (expiringSoon) filters.expiring_soon = '7';

        router.get(sales.index(companyId).url, filters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleClear = () => {
        setSearchCode('');
        setStatusFilter('all');
        setClientFilter('all');
        setServiceFilter('all');
        setAgentFilter('all');
        setExpiringSoon(false);
        router.get(sales.index(companyId).url);
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Ventas
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} venta{meta.total !== 1 ? 's' : ''}{' '}
                        registrada{meta.total !== 1 ? 's' : ''}
                    </p>
                </div>
                {can('sales.create') && (
                    <Button
                        className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                        onClick={() =>
                            router.visit(sales.create(companyId).url)
                        }
                    >
                        <Plus />
                        Nueva venta
                    </Button>
                )}
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div className="space-y-2">
                        <Label htmlFor="search-code">Código</Label>
                        <Input
                            id="search-code"
                            value={searchCode}
                            onChange={(e) => setSearchCode(e.target.value)}
                            onKeyDown={(e) =>
                                e.key === 'Enter' && handleSearch()
                            }
                            placeholder="Buscar por código..."
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
                                {SALE_STATUSES.map((s) => (
                                    <SelectItem key={s} value={s}>
                                        {SALE_STATUS_LABELS[s]}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="client-filter">Cliente</Label>
                        <Select
                            value={clientFilter}
                            onValueChange={setClientFilter}
                        >
                            <SelectTrigger id="client-filter">
                                <SelectValue placeholder="Todos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                {clients.map((c) => (
                                    <SelectItem key={c.id} value={c.id}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="service-filter">Servicio</Label>
                        <Select
                            value={serviceFilter}
                            onValueChange={setServiceFilter}
                        >
                            <SelectTrigger id="service-filter">
                                <SelectValue placeholder="Todos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                {services.map((s) => (
                                    <SelectItem key={s.id} value={s.id}>
                                        {s.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="agent-filter">Agente</Label>
                        <Select
                            value={agentFilter}
                            onValueChange={setAgentFilter}
                        >
                            <SelectTrigger id="agent-filter">
                                <SelectValue placeholder="Todos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                {agents.map((a) => (
                                    <SelectItem key={a.id} value={a.id}>
                                        {a.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="flex items-end">
                        <label className="inline-flex cursor-pointer items-center gap-2 text-sm font-medium">
                            <input
                                type="checkbox"
                                className="size-4"
                                checked={expiringSoon}
                                onChange={(e) =>
                                    setExpiringSoon(e.target.checked)
                                }
                            />
                            Por vencer (7 días)
                        </label>
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2fr_1.2fr_1fr_1.1fr_0.9fr_0.7fr]">
                    {[
                        'Código',
                        'Cliente',
                        'Servicio',
                        'Estado',
                        'Vence',
                        'Precio',
                        'Acciones',
                    ].map((h, i) => (
                        <div
                            key={h}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                i === 6 ? 'text-right' : ''
                            }`}
                        >
                            {h}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {items.map((sale) => (
                        <div
                            key={sale.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2fr_1.2fr_1fr_1.1fr_0.9fr_0.7fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    sales.show({
                                        company: companyId,
                                        id: sale.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {sale.code}
                                </span>
                            </div>
                            <div className="flex items-center gap-3">
                                <span className="grid size-10 shrink-0 place-items-center rounded-[11px] border bg-muted text-muted-foreground">
                                    <ShoppingCart className="size-5" />
                                </span>
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {sale.client?.name ?? '—'}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {sale.plan?.name ?? '—'}
                                    </span>
                                </div>
                            </div>
                            <div className="hidden lg:block">
                                <span className="text-[13.5px] font-semibold">
                                    {sale.service?.name ?? '—'}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill kind={saleStatusPill(sale.status)}>
                                    {SALE_STATUS_LABELS[sale.status]}
                                </StatusPill>
                            </div>
                            <div className="hidden lg:block">
                                <span className="font-semibold tabular-nums">
                                    {sale.end_date ?? '—'}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <span className="font-bold tabular-nums">
                                    {clp(Number(sale.price))}
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
                                                    sales.show({
                                                        company: companyId,
                                                        id: sale.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </DropdownMenuItem>
                                        {sale.can_be_renewed &&
                                            can('sales.renew') && (
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        setRenewSale(sale)
                                                    }
                                                >
                                                    <RefreshCw className="mr-2 h-4 w-4" />
                                                    Renovar
                                                </DropdownMenuItem>
                                            )}
                                        {sale.can_be_reactivated &&
                                            can('sales.reactivate') && (
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        router.visit(
                                                            sales.show({
                                                                company:
                                                                    companyId,
                                                                id: sale.id,
                                                            }).url,
                                                        )
                                                    }
                                                >
                                                    <RotateCcw className="mr-2 h-4 w-4" />
                                                    Reactivar
                                                </DropdownMenuItem>
                                            )}
                                        {sale.status !== 'cancelled' &&
                                            can('sales.cancel') && (
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        setCancelSale(sale)
                                                    }
                                                >
                                                    <Ban className="mr-2 h-4 w-4" />
                                                    Expulsar
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
                    {items.length} de {meta.total} venta
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>

            <SaleRenewDialog
                companyId={companyId}
                sale={renewSale}
                onClose={() => setRenewSale(null)}
            />
            <SaleCancelDialog
                companyId={companyId}
                sale={cancelSale}
                onClose={() => setCancelSale(null)}
            />
        </div>
    );
}
