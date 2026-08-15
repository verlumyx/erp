import { router, usePage } from '@inertiajs/react';
import { Award, BarChart3, Layers, Loader2, Search, Users } from 'lucide-react';
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
import reports from '@/routes/reports';
import type {
    GroupBy,
    ServicePlanFilters,
    ServicePlanMeta,
    ServicePlanRow,
    ServicePlanSummary,
} from '../types/ServicePlan';

interface ServicePlanReportProps {
    rows: ServicePlanRow[];
    summary: ServicePlanSummary;
    meta: ServicePlanMeta;
    filters: ServicePlanFilters;
    searched: boolean;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const CAPACITY_LABELS: Record<string, string> = {
    profile: 'Perfil',
    full_account: 'Cuenta completa',
};

function formatMoney(amount: number): string {
    return amount.toLocaleString('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export function ServicePlanReport({
    rows,
    summary,
    meta,
    filters: initialFilters,
    searched,
}: ServicePlanReportProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<ServicePlanFilters>(initialFilters);
    const [processing, setProcessing] = useState(false);

    const isPlan = filters.group_by === 'plan';

    const handleSearch = () => {
        router.get(
            reports.servicePlan.index(companyId).url,
            {
                date_from: filters.date_from,
                date_to: filters.date_to,
                group_by: filters.group_by,
                capacity: filters.capacity ?? '',
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
        router.get(reports.servicePlan.index(companyId).url, undefined, {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <div className="flex flex-col gap-5">
            <div>
                <h1 className="text-[27px] font-extrabold tracking-tight">
                    Reporte por Servicio / Plan
                </h1>
                <p className="mt-1 text-[14.5px] text-muted-foreground">
                    Desempeño comercial agrupado por servicio o por plan
                </p>
            </div>

            {searched && (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card className="gap-2 p-5">
                        <div className="flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                            <BarChart3 className="h-4 w-4" />
                            Ingreso total
                        </div>
                        <div className="text-2xl font-extrabold tabular-nums text-ok">
                            {formatMoney(summary.total_revenue)}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            {summary.total_sales} venta
                            {summary.total_sales !== 1 ? 's' : ''}
                        </div>
                    </Card>
                    <Card className="gap-2 p-5">
                        <div className="flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                            <Layers className="h-4 w-4" />
                            Ticket promedio
                        </div>
                        <div className="text-2xl font-extrabold tabular-nums">
                            {formatMoney(summary.avg_ticket)}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Ingreso ÷ ventas
                        </div>
                    </Card>
                    <Card className="gap-2 p-5">
                        <div className="flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                            <Users className="h-4 w-4" />
                            Mix de capacidad
                        </div>
                        <div className="text-2xl font-extrabold tabular-nums">
                            {summary.profile_count} / {summary.full_account_count}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Perfil / Cuenta completa
                        </div>
                    </Card>
                    <Card className="gap-2 p-5">
                        <div className="flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                            <Award className="h-4 w-4" />
                            {isPlan ? 'Plan top' : 'Servicio top'}
                        </div>
                        <div className="truncate text-2xl font-extrabold">
                            {summary.top_label ?? '—'}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            Mayor ingreso
                        </div>
                    </Card>
                </div>
            )}

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2">
                        <Label htmlFor="filter-date-from">Desde</Label>
                        <Input
                            id="filter-date-from"
                            type="date"
                            value={filters.date_from ?? ''}
                            onChange={(e) =>
                                setFilters({ ...filters, date_from: e.target.value })
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
                                setFilters({ ...filters, date_to: e.target.value })
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-group-by">Agrupar por</Label>
                        <Select
                            value={filters.group_by}
                            onValueChange={(value) =>
                                setFilters({ ...filters, group_by: value as GroupBy })
                            }
                        >
                            <SelectTrigger id="filter-group-by">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="service">Servicio</SelectItem>
                                <SelectItem value="plan">Plan</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-capacity">Capacidad</Label>
                        <Select
                            value={filters.capacity ?? 'all'}
                            onValueChange={(value) =>
                                setFilters({
                                    ...filters,
                                    capacity: value === 'all' ? null : value,
                                })
                            }
                        >
                            <SelectTrigger id="filter-capacity">
                                <SelectValue placeholder="Todas" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todas</SelectItem>
                                <SelectItem value="profile">Perfil</SelectItem>
                                <SelectItem value="full_account">
                                    Cuenta completa
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div className="mt-4 flex justify-end gap-2">
                    <Button onClick={handleSearch} variant="default" disabled={processing}>
                        {processing ? (
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        ) : (
                            <Search className="mr-2 h-4 w-4" />
                        )}
                        {processing ? 'Buscando…' : 'Buscar'}
                    </Button>
                    <Button onClick={handleClear} variant="outline" disabled={processing}>
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
                            <TableHead className="whitespace-nowrap">Código</TableHead>
                            <TableHead>{isPlan ? 'Plan' : 'Servicio'}</TableHead>
                            <TableHead className="text-right">Ventas</TableHead>
                            <TableHead className="text-right">Ingreso total</TableHead>
                            <TableHead className="text-right">Ticket prom.</TableHead>
                            <TableHead className="text-right">% ingreso</TableHead>
                            {isPlan && (
                                <>
                                    <TableHead className="text-right">
                                        Precio plan
                                    </TableHead>
                                    <TableHead className="text-right">
                                        ROI objetivo
                                    </TableHead>
                                </>
                            )}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row) => (
                            <TableRow key={row.id}>
                                <TableCell className="font-semibold whitespace-nowrap tabular-nums">
                                    {row.code ?? '—'}
                                </TableCell>
                                <TableCell>{row.name ?? '—'}</TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {row.sales_count}
                                </TableCell>
                                <TableCell className="text-right font-bold whitespace-nowrap tabular-nums text-ok">
                                    {formatMoney(row.revenue)}
                                </TableCell>
                                <TableCell className="text-right whitespace-nowrap tabular-nums">
                                    {formatMoney(row.avg_ticket)}
                                </TableCell>
                                <TableCell className="text-right tabular-nums text-muted-foreground">
                                    {row.revenue_pct.toFixed(1)}%
                                </TableCell>
                                {isPlan && (
                                    <>
                                        <TableCell className="text-right whitespace-nowrap tabular-nums text-muted-foreground">
                                            {row.sale_price != null
                                                ? formatMoney(row.sale_price)
                                                : '—'}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums text-muted-foreground">
                                            {row.roi_target_pct != null
                                                ? `${row.roi_target_pct.toFixed(1)}%`
                                                : '—'}
                                        </TableCell>
                                    </>
                                )}
                            </TableRow>
                        ))}
                        {rows.length === 0 && (
                            <TableRow className="hover:bg-transparent">
                                <TableCell
                                    colSpan={isPlan ? 8 : 6}
                                    className="p-12 text-center text-sm text-muted-foreground"
                                >
                                    {searched
                                        ? 'Sin ventas para los filtros seleccionados.'
                                        : 'Aplica los filtros y presiona Buscar para ver el reporte.'}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
                {rows.length > 0 && (
                    <div className="flex items-center justify-between border-t px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                        <span>
                            {rows.length} de {meta.total}{' '}
                            {isPlan ? 'plan' : 'servicio'}
                            {meta.total !== 1 ? 's' : ''}
                        </span>
                        <span className="tabular-nums">
                            Total: {formatMoney(summary.total_revenue)}
                        </span>
                    </div>
                )}
            </Card>
        </div>
    );
}
