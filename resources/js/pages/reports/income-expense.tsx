import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
    Search,
    RotateCcw,
    DollarSign,
    TrendingUp,
    TrendingDown,
    ArrowDownLeft,
    ArrowUpRight,
    Wallet,
    Receipt,
    CreditCard,
    Building2,
    Calendar,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Pagination } from '@/components/pagination';
import { formatAmount } from '@/lib/money';
import type { BreadcrumbItem } from '@/types';

interface CashFlowRow {
    id: string;
    date: string;
    direction: 'inflow' | 'outflow';
    type_label: string;
    concept: string;
    entity_name: string;
    reference?: string | null;
    payment_method: string;
    payment_method_label: string;
    currency: string;
    amount_original: number;
    amount_usd: number;
    amount_ves: number;
}

interface AccrualRow {
    id: string;
    date: string;
    type: 'sale' | 'credit_note' | 'purchase';
    type_label: string;
    code: string;
    entity_name: string;
    currency: string;
    amount_original: number;
    subtotal_usd: number;
    subtotal_ves: number;
    cost_usd: number;
    cost_ves: number;
}

interface MethodBreakdown {
    method: string;
    label: string;
    inflow_usd: number;
    outflow_usd: number;
    net_usd: number;
    inflow_ves: number;
    outflow_ves: number;
    net_ves: number;
}

interface CashFlowSummary {
    total_inflow_usd: number;
    total_inflow_ves: number;
    total_outflow_usd: number;
    total_outflow_ves: number;
    net_flow_usd: number;
    net_flow_ves: number;
    exchange_rate: number;
}

interface AccrualSummary {
    gross_sales_usd: number;
    credit_notes_usd: number;
    net_sales_usd: number;
    net_sales_ves: number;
    cmv_usd: number;
    cmv_ves: number;
    gross_profit_usd: number;
    gross_profit_ves: number;
    gross_margin_percent: number;
    operating_expenses_usd: number;
    operating_expenses_ves: number;
    operating_result_usd: number;
    operating_result_ves: number;
    operating_margin_percent: number;
    exchange_rate: number;
}

interface MetaData {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

interface FilterData {
    consulted?: boolean;
    from_date: string;
    to_date: string;
    report_basis: 'cash_flow' | 'accrual';
    payment_method?: string | null;
    currency_view?: string;
    limit: number;
    offset: number;
}

interface OptionsData {
    payment_methods?: { value: string; label: string }[];
}

interface Props {
    consulted?: boolean;
    report_basis: 'cash_flow' | 'accrual';
    summary: CashFlowSummary & AccrualSummary;
    breakdown?: MethodBreakdown[];
    rows: (CashFlowRow | AccrualRow)[];
    meta: MetaData;
    filters: FilterData;
    options: OptionsData;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function IncomeExpenseReport({
    consulted = false,
    report_basis,
    summary,
    breakdown = [],
    rows,
    meta,
    filters: initialFilters,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany?.id ?? '';

    const [filters, setFilters] = useState<FilterData>({ ...initialFilters, consulted });

    const applyFilters = (newFilters: Partial<FilterData>) => {
        const next = { ...filters, ...newFilters, consulted: true, offset: newFilters.offset ?? 0 };
        setFilters(next);
        router.get(`/${companyId}/reports/income-expense`, next as unknown as Record<string, string>, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const switchBasis = (basis: 'cash_flow' | 'accrual') => {
        applyFilters({ report_basis: basis });
    };

    const handleReset = () => {
        const defaultFilters: FilterData = {
            consulted: false,
            from_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
            to_date: new Date().toISOString().split('T')[0],
            report_basis: report_basis,
            payment_method: 'all',
            limit: 15,
            offset: 0,
        };
        setFilters(defaultFilters);
        router.get(`/${companyId}/reports/income-expense`);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Reportes',
            href: `/${companyId}/reports/income-expense`,
        },
        {
            title: 'Ingresos y Gastos',
            href: `/${companyId}/reports/income-expense`,
        },
    ];

    const paymentMethodOptions: OptionType[] = [
        { value: 'all', label: 'Todos los métodos' },
        ...(options.payment_methods ?? []),
    ];

    const isCashFlow = report_basis === 'cash_flow';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reporte de Ingresos y Gastos" />

            <div className="mx-auto w-full max-w-7xl space-y-6 p-6 pb-16">
                {/* Header & Tabs */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Reporte de Ingresos y Gastos
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Análisis comparativo de flujos de fondos y resultados operativos del período.
                        </p>
                    </div>

                    {/* Selector de Criterio: Caja vs Devengado */}
                    <div className="flex rounded-lg border bg-muted/40 p-1">
                        <button
                            type="button"
                            onClick={() => switchBasis('cash_flow')}
                            className={`flex items-center gap-2 rounded-md px-3.5 py-1.5 text-xs font-semibold transition-all ${
                                isCashFlow
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            <Wallet className="h-3.5 w-3.5" />
                            Criterio de Caja (Flujo Real)
                        </button>
                        <button
                            type="button"
                            onClick={() => switchBasis('accrual')}
                            className={`flex items-center gap-2 rounded-md px-3.5 py-1.5 text-xs font-semibold transition-all ${
                                !isCashFlow
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            <Receipt className="h-3.5 w-3.5" />
                            Criterio Devengado (Económico)
                        </button>
                    </div>
                </div>

                {/* KPI Summary Cards */}
                {consulted && (
                    isCashFlow ? (
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <Card className="border-l-4 border-l-emerald-600 shadow-sm bg-emerald-500/5">
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-bold uppercase text-emerald-700 dark:text-emerald-400">
                                            Total Ingresos Recibidos
                                        </span>
                                        <ArrowDownLeft className="h-4 w-4 text-emerald-600" />
                                    </div>
                                    <div className="mt-1 text-2xl font-extrabold tracking-tight text-emerald-700 dark:text-emerald-400 tabular-nums">
                                        ${formatAmount(summary.total_inflow_usd)}
                                    </div>
                                    <span className="text-xs text-muted-foreground">
                                        Bs. {formatAmount(summary.total_inflow_ves)}
                                    </span>
                                </CardContent>
                            </Card>

                            <Card className="border-l-4 border-l-destructive shadow-sm bg-destructive/5">
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-bold uppercase text-destructive">
                                            Total Egresos Pagados
                                        </span>
                                        <ArrowUpRight className="h-4 w-4 text-destructive" />
                                    </div>
                                    <div className="mt-1 text-2xl font-extrabold tracking-tight text-destructive tabular-nums">
                                        ${formatAmount(summary.total_outflow_usd)}
                                    </div>
                                    <span className="text-xs text-muted-foreground">
                                        Bs. {formatAmount(summary.total_outflow_ves)}
                                    </span>
                                </CardContent>
                            </Card>

                            <Card
                                className={`border-l-4 shadow-sm ${
                                    summary.net_flow_usd >= 0
                                        ? 'border-l-emerald-600 bg-emerald-500/10'
                                        : 'border-l-destructive bg-destructive/10'
                                }`}
                            >
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <span
                                            className={`text-xs font-bold uppercase ${
                                                summary.net_flow_usd >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-destructive'
                                            }`}
                                        >
                                            Flujo Neto de Caja ({summary.net_flow_usd >= 0 ? 'Superávit' : 'Déficit'})
                                        </span>
                                        {summary.net_flow_usd >= 0 ? (
                                            <TrendingUp className="h-4 w-4 text-emerald-600" />
                                        ) : (
                                            <TrendingDown className="h-4 w-4 text-destructive" />
                                        )}
                                    </div>
                                    <div
                                        className={`mt-1 text-2xl font-extrabold tracking-tight tabular-nums ${
                                            summary.net_flow_usd >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-destructive'
                                        }`}
                                    >
                                        ${formatAmount(summary.net_flow_usd)}
                                    </div>
                                    <span className="text-xs text-muted-foreground">
                                        Bs. {formatAmount(summary.net_flow_ves)}
                                    </span>
                                </CardContent>
                            </Card>
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                            <Card className="border-l-4 border-l-primary shadow-sm">
                                <CardContent className="p-4">
                                    <span className="text-xs font-semibold uppercase text-muted-foreground">Ventas Netas</span>
                                    <div className="mt-1 text-xl font-bold tracking-tight text-foreground tabular-nums">
                                        ${formatAmount(summary.net_sales_usd)}
                                    </div>
                                    <span className="text-[11px] text-muted-foreground">Facturado sin N/C</span>
                                </CardContent>
                            </Card>

                            <Card className="border-l-4 border-l-amber-500 shadow-sm">
                                <CardContent className="p-4">
                                    <span className="text-xs font-semibold uppercase text-muted-foreground">(-) CMV (Costo)</span>
                                    <div className="mt-1 text-xl font-bold tracking-tight text-amber-600 dark:text-amber-400 tabular-nums">
                                        ${formatAmount(summary.cmv_usd)}
                                    </div>
                                    <span className="text-[11px] text-muted-foreground">Costo mercancía vendida</span>
                                </CardContent>
                            </Card>

                            <Card className="border-l-4 border-l-emerald-600 shadow-sm bg-emerald-500/5">
                                <CardContent className="p-4">
                                    <span className="text-xs font-bold uppercase text-emerald-700 dark:text-emerald-400">Utilidad Bruta</span>
                                    <div className="mt-1 text-xl font-extrabold tracking-tight text-emerald-700 dark:text-emerald-400 tabular-nums">
                                        ${formatAmount(summary.gross_profit_usd)}
                                    </div>
                                    <span className="text-[11px] font-semibold text-emerald-600">
                                        Margen {summary.gross_margin_percent}%
                                    </span>
                                </CardContent>
                            </Card>

                            <Card className="border-l-4 border-l-rose-500 shadow-sm">
                                <CardContent className="p-4">
                                    <span className="text-xs font-semibold uppercase text-muted-foreground">(-) Gastos Operativos</span>
                                    <div className="mt-1 text-xl font-bold tracking-tight text-rose-600 dark:text-rose-400 tabular-nums">
                                        ${formatAmount(summary.operating_expenses_usd)}
                                    </div>
                                    <span className="text-[11px] text-muted-foreground">Compras y gastos caja</span>
                                </CardContent>
                            </Card>

                            <Card
                                className={`border-l-4 shadow-sm ${
                                    summary.operating_result_usd >= 0
                                        ? 'border-l-emerald-600 bg-emerald-500/10'
                                        : 'border-l-destructive bg-destructive/10'
                                }`}
                            >
                                <CardContent className="p-4">
                                    <span
                                        className={`text-xs font-bold uppercase ${
                                            summary.operating_result_usd >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-destructive'
                                        }`}
                                    >
                                        Resultado Operativo
                                    </span>
                                    <div
                                        className={`mt-1 text-xl font-extrabold tracking-tight tabular-nums ${
                                            summary.operating_result_usd >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-destructive'
                                        }`}
                                    >
                                        ${formatAmount(summary.operating_result_usd)}
                                    </div>
                                    <span className="text-[11px] text-muted-foreground">
                                        {summary.operating_result_usd >= 0 ? 'Utilidad neta' : 'Pérdida'}
                                    </span>
                                </CardContent>
                            </Card>

                            <Card className="border-l-4 border-l-sky-500 shadow-sm">
                                <CardContent className="p-4">
                                    <span className="text-xs font-semibold uppercase text-muted-foreground">% Margen Operativo</span>
                                    <div className="mt-1 text-xl font-bold tracking-tight text-sky-600 dark:text-sky-400 tabular-nums">
                                        {summary.operating_margin_percent}%
                                    </div>
                                    <span className="text-[11px] text-muted-foreground">Rentabilidad sobre ventas</span>
                                </CardContent>
                            </Card>
                        </div>
                    )
                )}

                {/* Filter Bar */}
                <Card className="shadow-sm">
                    <CardContent className="p-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="from_date" className="text-xs font-semibold text-muted-foreground">
                                    Desde
                                </Label>
                                <Input
                                    id="from_date"
                                    type="date"
                                    value={filters.from_date}
                                    onChange={(e) => setFilters({ ...filters, from_date: e.target.value })}
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="to_date" className="text-xs font-semibold text-muted-foreground">
                                    Hasta
                                </Label>
                                <Input
                                    id="to_date"
                                    type="date"
                                    value={filters.to_date}
                                    onChange={(e) => setFilters({ ...filters, to_date: e.target.value })}
                                />
                            </div>

                            {isCashFlow && (
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-muted-foreground">Medio de Pago</Label>
                                    <Select2
                                        options={paymentMethodOptions}
                                        value={
                                            paymentMethodOptions.find(
                                                (o) => o.value === (filters.payment_method ?? 'all'),
                                            ) ?? null
                                        }
                                        onChange={(opt) =>
                                            setFilters({
                                                ...filters,
                                                payment_method: opt && opt.value !== 'all' ? opt.value : 'all',
                                            })
                                        }
                                        placeholder="Todos los métodos"
                                    />
                                </div>
                            )}
                        </div>

                        <div className="mt-4 flex items-center justify-between border-t pt-3">
                            <span className="text-xs text-muted-foreground">
                                Tasa referencial oficial del período: <strong>Bs. {formatAmount(summary.exchange_rate)} / USD</strong>
                            </span>
                            <div className="flex gap-2">
                                <Button variant="outline" size="sm" onClick={handleReset}>
                                    <RotateCcw className="mr-1.5 h-3.5 w-3.5" />
                                    Limpiar
                                </Button>
                                <Button size="sm" onClick={() => applyFilters({ consulted: true })}>
                                    <Search className="mr-1.5 h-3.5 w-3.5" />
                                    Consultar
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Desglose por Medio de Pago (Solo en Criterio de Caja) */}
                {consulted && isCashFlow && breakdown.length > 0 && (
                    <Card className="shadow-sm">
                        <CardHeader className="py-3 px-5 border-b bg-muted/40">
                            <CardTitle className="text-sm font-semibold text-foreground flex items-center gap-2">
                                <CreditCard className="h-4 w-4 text-primary" />
                                Desglose por Medio de Pago y Cuenta
                            </CardTitle>
                        </CardHeader>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead className="border-b bg-muted/20 font-semibold text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-2.5">Medio de Pago</th>
                                        <th className="px-4 py-2.5 text-right text-emerald-600">Ingresos USD</th>
                                        <th className="px-4 py-2.5 text-right text-destructive">Egresos USD</th>
                                        <th className="px-4 py-2.5 text-right font-bold text-foreground">Flujo Neto USD</th>
                                        <th className="px-4 py-2.5 text-right text-muted-foreground">Flujo Neto VES</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {breakdown.map((b) => (
                                        <tr key={b.method} className="transition-colors hover:bg-muted/30">
                                            <td className="px-4 py-2 font-medium text-foreground">{b.label}</td>
                                            <td className="px-4 py-2 text-right tabular-nums text-emerald-600 font-semibold">
                                                ${formatAmount(b.inflow_usd)}
                                            </td>
                                            <td className="px-4 py-2 text-right tabular-nums text-destructive font-semibold">
                                                ${formatAmount(b.outflow_usd)}
                                            </td>
                                            <td
                                                className={`px-4 py-2 text-right tabular-nums font-bold ${
                                                    b.net_usd >= 0 ? 'text-emerald-600' : 'text-destructive'
                                                }`}
                                            >
                                                ${formatAmount(b.net_usd)}
                                            </td>
                                            <td className="px-4 py-2 text-right tabular-nums text-muted-foreground">
                                                Bs. {formatAmount(b.net_ves)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                )}

                {/* Tabla de Movimientos Detallados o Estado Vacío Pre-Consulta */}
                <Card className="shadow-sm">
                    {!consulted ? (
                        <div className="flex flex-col items-center justify-center p-12 text-center">
                            <div className="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary mb-4">
                                <Search className="h-7 w-7" />
                            </div>
                            <h3 className="text-base font-semibold text-foreground">Reporte listo para consultar</h3>
                            <p className="mt-1 max-w-md text-sm text-muted-foreground">
                                Seleccione el rango de fechas y los criterios deseados arriba, y presione «Consultar» para cargar la información.
                            </p>
                            <Button className="mt-4" onClick={() => applyFilters({ consulted: true })}>
                                <Search className="mr-2 h-4 w-4" />
                                Consultar
                            </Button>
                        </div>
                    ) : (
                        <>
                            <CardHeader className="py-3 px-5 border-b bg-muted/40">
                                <CardTitle className="text-sm font-semibold text-foreground flex items-center gap-2">
                                    <Receipt className="h-4 w-4 text-primary" />
                                    {isCashFlow
                                        ? 'Detalle de Movimientos de Tesorería'
                                        : 'Detalle de Operaciones Devengadas (Ventas, Compras y Gastos)'}
                                </CardTitle>
                            </CardHeader>
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs sm:text-sm">
                                    <thead className="border-b bg-muted/60 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3">Fecha</th>
                                            <th className="px-4 py-3">Tipo</th>
                                            <th className="px-4 py-3">Concepto / N° Doc</th>
                                            <th className="px-4 py-3">Tercero / Entidad</th>
                                            {isCashFlow && <th className="px-3 py-3">Medio de Pago</th>}
                                            <th className="px-3 py-3 text-right">Monto Original</th>
                                            <th className="px-3 py-3 text-right font-semibold text-foreground">Monto USD</th>
                                            <th className="px-4 py-3 text-right text-muted-foreground">Monto VES</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {rows.length === 0 ? (
                                            <tr>
                                                <td
                                                    colSpan={isCashFlow ? 8 : 7}
                                                    className="py-12 text-center text-sm text-muted-foreground"
                                                >
                                                    No se registraron transacciones para el período y criterios seleccionados.
                                                </td>
                                            </tr>
                                        ) : (
                                            rows.map((row) => {
                                                if (isCashFlow) {
                                                    const m = row as CashFlowRow;
                                                    const isInflow = m.direction === 'inflow';
                                                    return (
                                                        <tr key={m.id} className="transition-colors hover:bg-muted/40">
                                                            <td className="px-4 py-3 tabular-nums text-muted-foreground">{m.date}</td>
                                                            <td className="px-4 py-3">
                                                                <Badge
                                                                    variant={isInflow ? 'secondary' : 'outline'}
                                                                    className={`text-[10px] uppercase font-bold ${
                                                                        isInflow
                                                                            ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                                                            : 'border-destructive/40 text-destructive'
                                                                    }`}
                                                                >
                                                                    {isInflow ? '+ Ingreso' : '- Egreso'}
                                                                </Badge>
                                                            </td>
                                                            <td className="px-4 py-3 font-medium text-foreground">
                                                                <div className="flex flex-col">
                                                                    <span>{m.concept}</span>
                                                                    {m.reference && (
                                                                        <span className="text-[11px] text-muted-foreground">
                                                                            Ref: {m.reference}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-3 text-muted-foreground">{m.entity_name}</td>
                                                            <td className="px-3 py-3">
                                                                <span className="rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                                                    {m.payment_method_label}
                                                                </span>
                                                            </td>
                                                            <td className="px-3 py-3 text-right tabular-nums text-muted-foreground">
                                                                {m.currency} {formatAmount(m.amount_original)}
                                                            </td>
                                                            <td
                                                                className={`px-3 py-3 text-right tabular-nums font-bold ${
                                                                    isInflow ? 'text-emerald-600' : 'text-destructive'
                                                                }`}
                                                            >
                                                                {isInflow ? '+' : '-'}${formatAmount(m.amount_usd)}
                                                            </td>
                                                            <td className="px-4 py-3 text-right tabular-nums text-muted-foreground">
                                                                Bs. {formatAmount(m.amount_ves)}
                                                            </td>
                                                        </tr>
                                                    );
                                                }

                                                const doc = row as AccrualRow;
                                                const isSale = doc.type === 'sale';
                                                return (
                                                    <tr key={doc.id} className="transition-colors hover:bg-muted/40">
                                                        <td className="px-4 py-3 tabular-nums text-muted-foreground">{doc.date}</td>
                                                        <td className="px-4 py-3">
                                                            <Badge
                                                                variant="outline"
                                                                className={`text-[10px] uppercase font-bold ${
                                                                    isSale
                                                                        ? 'border-emerald-500/40 text-emerald-600'
                                                                        : doc.type === 'credit_note'
                                                                        ? 'border-amber-500/40 text-amber-600'
                                                                        : 'border-rose-500/40 text-rose-600'
                                                                }`}
                                                            >
                                                                {doc.type_label}
                                                            </Badge>
                                                        </td>
                                                        <td className="px-4 py-3 font-medium text-foreground tabular-nums">
                                                            {doc.code}
                                                        </td>
                                                        <td className="px-4 py-3 text-muted-foreground">{doc.entity_name}</td>
                                                        <td className="px-3 py-3 text-right tabular-nums text-muted-foreground">
                                                            {doc.currency} {formatAmount(doc.amount_original)}
                                                        </td>
                                                        <td
                                                            className={`px-3 py-3 text-right tabular-nums font-semibold ${
                                                                doc.subtotal_usd >= 0 ? 'text-foreground' : 'text-destructive'
                                                            }`}
                                                        >
                                                            ${formatAmount(doc.subtotal_usd)}
                                                        </td>
                                                        <td className="px-4 py-3 text-right tabular-nums text-muted-foreground">
                                                            Bs. {formatAmount(doc.subtotal_ves)}
                                                        </td>
                                                    </tr>
                                                );
                                            })
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Paginación de transacciones */}
                            <Pagination
                                meta={meta}
                                onPageChange={(newOffset) => {
                                    applyFilters({ offset: newOffset });
                                }}
                                label="movimiento"
                                labelPlural="movimientos"
                            />
                        </>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
