import { router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarClock,
    Loader2,
    RefreshCw,
    Repeat,
    Search,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { SaleRenewDialog } from '@/pages/sales/components/SaleRenewDialog';
import type { Sale } from '@/pages/sales/types/Sale';
import reports from '@/routes/reports';
import type {
    ExpirationAgentOption,
    ExpirationFilters,
    ExpirationMeta,
    ExpirationServiceOption,
    ExpirationStatus,
    ExpirationSummary,
} from '../types/Expiration';

interface ExpirationReportProps {
    sales: Sale[];
    summary: ExpirationSummary;
    services: ExpirationServiceOption[];
    agents: ExpirationAgentOption[];
    meta: ExpirationMeta;
    filters: ExpirationFilters;
    searched: boolean;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function formatMoney(amount: number): string {
    return amount.toLocaleString('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

/** Texto de días restantes: negativo si ya venció. */
function remainingLabel(days: number): string {
    if (days === 0) {
        return 'Hoy';
    }
    if (days < 0) {
        return `Vencida hace ${Math.abs(days)} d`;
    }
    return `En ${days} d`;
}

export function ExpirationReport({
    sales,
    summary,
    services,
    agents,
    meta,
    filters: initialFilters,
    searched,
}: ExpirationReportProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<ExpirationFilters>(initialFilters);
    const [processing, setProcessing] = useState(false);
    const [renewSale, setRenewSale] = useState<Sale | null>(null);

    const handleSearch = () => {
        router.get(
            reports.expirations.index(companyId).url,
            {
                days: String(filters.days),
                status: filters.status,
                date_from: filters.date_from ?? '',
                date_to: filters.date_to ?? '',
                service_id: filters.service_id ?? '',
                agent_id: filters.agent_id ?? '',
                searched: '1',
            } as unknown as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    };

    const handleClear = () => {
        router.get(reports.expirations.index(companyId).url, undefined, {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <div className="flex flex-col gap-5">
            <div>
                <h1 className="text-[27px] font-extrabold tracking-tight">
                    Reporte de Vencimientos
                </h1>
                <p className="mt-1 text-[14.5px] text-muted-foreground">
                    Ventas próximas a vencer y vencidas sin renovar
                </p>
            </div>

            {searched && (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card className="gap-2 p-5">
                        <div className="flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                            <CalendarClock className="h-4 w-4 text-amber-500" />
                            Por vencer
                        </div>
                        <div className="text-2xl font-extrabold tabular-nums">
                            {summary.expiring_count}
                        </div>
                        <div className="text-xs text-muted-foreground tabular-nums">
                            {formatMoney(summary.expiring_amount)} en juego
                        </div>
                    </Card>
                    <Card className="gap-2 p-5">
                        <div className="flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                            <AlertTriangle className="h-4 w-4 text-bad" />
                            Vencidas
                        </div>
                        <div className="text-2xl font-extrabold text-bad tabular-nums">
                            {summary.expired_count}
                        </div>
                        <div className="text-xs text-muted-foreground tabular-nums">
                            sin renovar
                        </div>
                    </Card>
                    <Card className="gap-2 p-5">
                        <div className="flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                            <Wallet className="h-4 w-4" />
                            Por cobrar
                        </div>
                        <div className="text-2xl font-extrabold text-bad tabular-nums">
                            {formatMoney(summary.expired_amount)}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Base de vencidas
                        </div>
                    </Card>
                    <Card className="gap-2 p-5">
                        <div className="flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                            <Repeat className="h-4 w-4 text-ok" />
                            Tasa de renovación
                        </div>
                        <div className="text-2xl font-extrabold text-ok tabular-nums">
                            {summary.renewal_rate.toFixed(1)}%
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Renovadas ÷ vencidas
                        </div>
                    </Card>
                </div>
            )}

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div className="space-y-2">
                        <Label htmlFor="filter-status">Estado</Label>
                        <Select
                            value={filters.status}
                            onValueChange={(value) =>
                                setFilters({
                                    ...filters,
                                    status: value as ExpirationStatus,
                                })
                            }
                        >
                            <SelectTrigger id="filter-status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="expiring">
                                    Por vencer
                                </SelectItem>
                                <SelectItem value="expired">
                                    Vencidas
                                </SelectItem>
                                <SelectItem value="all">Todas</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-days">Próximos días</Label>
                        <Select
                            value={String(filters.days)}
                            onValueChange={(value) =>
                                setFilters({ ...filters, days: Number(value) })
                            }
                        >
                            <SelectTrigger id="filter-days">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="7">7 días</SelectItem>
                                <SelectItem value="15">15 días</SelectItem>
                                <SelectItem value="30">30 días</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-service">Servicio</Label>
                        <Select
                            value={filters.service_id ?? 'all'}
                            onValueChange={(value) =>
                                setFilters({
                                    ...filters,
                                    service_id: value === 'all' ? null : value,
                                })
                            }
                        >
                            <SelectTrigger id="filter-service">
                                <SelectValue placeholder="Todos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                {services.map((service) => (
                                    <SelectItem
                                        key={service.id}
                                        value={service.id}
                                    >
                                        {service.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-agent">Agente</Label>
                        <Select
                            value={filters.agent_id ?? 'all'}
                            onValueChange={(value) =>
                                setFilters({
                                    ...filters,
                                    agent_id: value === 'all' ? null : value,
                                })
                            }
                        >
                            <SelectTrigger id="filter-agent">
                                <SelectValue placeholder="Todos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                {agents.map((agent) => (
                                    <SelectItem key={agent.id} value={agent.id}>
                                        {agent.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-date-from">
                            Desde (vencimiento)
                        </Label>
                        <Input
                            id="filter-date-from"
                            type="date"
                            value={filters.date_from ?? ''}
                            onChange={(e) =>
                                setFilters({
                                    ...filters,
                                    date_from: e.target.value || null,
                                })
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-date-to">
                            Hasta (vencimiento)
                        </Label>
                        <Input
                            id="filter-date-to"
                            type="date"
                            value={filters.date_to ?? ''}
                            onChange={(e) =>
                                setFilters({
                                    ...filters,
                                    date_to: e.target.value || null,
                                })
                            }
                        />
                    </div>
                </div>
                <div className="mt-4 flex justify-end gap-2">
                    <Button
                        onClick={handleSearch}
                        variant="default"
                        disabled={processing}
                    >
                        {processing ? (
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        ) : (
                            <Search className="mr-2 h-4 w-4" />
                        )}
                        {processing ? 'Buscando…' : 'Buscar'}
                    </Button>
                    <Button
                        onClick={handleClear}
                        variant="outline"
                        disabled={processing}
                    >
                        Limpiar
                    </Button>
                </div>
            </div>

            <Card className="relative overflow-hidden rounded-2xl py-0">
                {processing && (
                    <div className="absolute inset-0 z-10 flex items-center justify-center bg-background/60 backdrop-blur-[1px]">
                        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                    </div>
                )}
                <Table>
                    <TableHeader>
                        <TableRow className="bg-muted hover:bg-muted">
                            <TableHead className="whitespace-nowrap">
                                Código
                            </TableHead>
                            <TableHead>Cliente</TableHead>
                            <TableHead>Servicio / Plan</TableHead>
                            <TableHead className="text-right">Precio</TableHead>
                            <TableHead className="whitespace-nowrap">
                                Vencimiento
                            </TableHead>
                            <TableHead className="whitespace-nowrap">
                                Restantes
                            </TableHead>
                            <TableHead>Agente</TableHead>
                            <TableHead className="text-right">Acción</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {sales.map((sale) => {
                            const isExpired = sale.status === 'expired';
                            const isUrgent =
                                sale.status === 'active' &&
                                sale.days_until_expiration >= 0 &&
                                sale.days_until_expiration <= 3;

                            return (
                                <TableRow
                                    key={sale.id}
                                    className={
                                        isExpired
                                            ? 'bg-bad-soft/40 hover:bg-bad-soft/60'
                                            : isUrgent
                                              ? 'bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/30 dark:hover:bg-amber-950/50'
                                              : undefined
                                    }
                                >
                                    <TableCell className="font-semibold whitespace-nowrap tabular-nums">
                                        {sale.code}
                                    </TableCell>
                                    <TableCell>
                                        {sale.client?.name ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <div className="font-medium">
                                            {sale.service?.name ?? '—'}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {sale.plan?.name ?? '—'}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-right font-bold whitespace-nowrap tabular-nums">
                                        {formatMoney(Number(sale.price))}
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap tabular-nums">
                                        <div className="flex items-center gap-2">
                                            {sale.end_date ?? '—'}
                                            {sale.is_in_grace_period && (
                                                <span className="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700 dark:bg-amber-950/50 dark:text-amber-400">
                                                    Gracia
                                                </span>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell
                                        className={`whitespace-nowrap tabular-nums ${
                                            sale.days_until_expiration < 0
                                                ? 'font-semibold text-bad'
                                                : isUrgent
                                                  ? 'font-semibold text-amber-600'
                                                  : 'text-muted-foreground'
                                        }`}
                                    >
                                        {remainingLabel(
                                            sale.days_until_expiration,
                                        )}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {sale.agent?.name ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            disabled={!sale.can_be_renewed}
                                            onClick={() => setRenewSale(sale)}
                                        >
                                            <RefreshCw className="mr-1.5 h-3.5 w-3.5" />
                                            Renovar
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                        {sales.length === 0 && (
                            <TableRow className="hover:bg-transparent">
                                <TableCell
                                    colSpan={8}
                                    className="p-12 text-center text-sm text-muted-foreground"
                                >
                                    {searched
                                        ? 'Sin vencimientos para los filtros seleccionados.'
                                        : 'Aplica los filtros y presiona Buscar para ver los vencimientos.'}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
                {sales.length > 0 && (
                    <div className="border-t px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                        {sales.length} de {meta.total} venta
                        {meta.total !== 1 ? 's' : ''}
                    </div>
                )}
            </Card>

            <SaleRenewDialog
                companyId={companyId}
                sale={renewSale}
                onClose={() => setRenewSale(null)}
            />
        </div>
    );
}
