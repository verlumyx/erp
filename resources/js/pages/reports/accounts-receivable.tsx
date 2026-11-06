import { useState, Fragment } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronRight,
    Search,
    RotateCcw,
    DollarSign,
    Clock,
    AlertTriangle,
    ShieldAlert,
    FileText,
    Users,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Pagination } from '@/components/pagination';
import { formatAmount } from '@/lib/money';
import type { BreadcrumbItem } from '@/types';

interface InvoiceRow {
    id: string;
    code: string;
    invoice_series?: string | null;
    invoice_number?: string | null;
    full_number: string;
    invoice_date: string;
    due_date: string;
    days_credit: number;
    days_overdue: number;
    is_overdue: boolean;
    salesperson_name: string;
    currency: string;
    total: number;
    paid_amount: number;
    balance: number;
    balance_usd: number;
    balance_ves: number;
    payment_status: string;
}

interface ClientRow {
    client: {
        id: string;
        code: string;
        name: string;
        legal_name?: string | null;
        tax_number: string;
        document_type: string;
        document_number: string;
        credit_limit: number;
        credit_usage_percent: number;
    };
    aging: {
        current: number;
        days_1_30: number;
        days_31_60: number;
        days_61_90: number;
        days_over_90: number;
    };
    gross_debt: number;
    gross_debt_usd: number;
    gross_debt_ves: number;
    advances_balance: number;
    credit_notes_balance: number;
    net_debt: number;
    invoices_count: number;
    invoices: InvoiceRow[];
}

interface SummaryData {
    total_gross_debt: number;
    total_advances: number;
    total_credit_notes: number;
    total_net_debt: number;
    total_current: number;
    total_overdue: number;
    total_clients: number;
    total_invoices: number;
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
    as_of_date: string;
    client_id?: string | null;
    salesperson_id?: string | null;
    payment_status?: string | null;
    currency_view?: string;
    limit: number;
    offset: number;
}

interface OptionsData {
    clients: { value: string; label: string }[];
    salespersons: { value: string; label: string }[];
}

interface Props {
    consulted: boolean;
    rows: ClientRow[];
    summary: SummaryData;
    meta: MetaData;
    filters: FilterData;
    options: OptionsData;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const PAYMENT_STATUS_OPTIONS: OptionType[] = [
    { value: 'all', label: 'Todas las deudas' },
    { value: 'overdue', label: 'Solo facturas vencidas (Mora)' },
    { value: 'pending', label: 'Sin abonos previos' },
    { value: 'partial', label: 'Con abonos parciales' },
];

function ClientSubtable({ invoices }: { invoices: InvoiceRow[] }) {
    const [subOffset, setSubOffset] = useState(0);
    const subLimit = 5;
    const currentInvoices = invoices.slice(subOffset, subOffset + subLimit);

    return (
        <div className="rounded-lg border bg-card p-4 shadow-sm">
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <FileText className="h-4 w-4 text-primary" />
                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Facturas pendientes de cobro
                    </span>
                    <Badge variant="secondary" className="text-xs font-semibold">
                        {invoices.length} {invoices.length === 1 ? 'documento' : 'documentos'}
                    </Badge>
                </div>
            </div>

            <div className="overflow-x-auto rounded border">
                <table className="w-full text-left text-xs">
                    <thead className="border-b bg-muted/60 font-semibold text-muted-foreground">
                        <tr>
                            <th className="px-3 py-2">N° Factura / Control</th>
                            <th className="px-3 py-2">Emisión</th>
                            <th className="px-3 py-2">Vencimiento</th>
                            <th className="px-3 py-2">Mora / Estado</th>
                            <th className="px-3 py-2">Vendedor</th>
                            <th className="px-3 py-2 text-right">Total</th>
                            <th className="px-3 py-2 text-right">Abonado</th>
                            <th className="px-3 py-2 text-right">Saldo Original</th>
                            <th className="px-3 py-2 text-right">Saldo USD</th>
                            <th className="px-3 py-2 text-right">Saldo VES</th>
                            <th className="px-3 py-2 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                        {currentInvoices.map((inv) => (
                            <tr key={inv.id} className="transition-colors hover:bg-muted/40">
                                <td className="px-3 py-2.5 font-medium tabular-nums">
                                    <div className="flex flex-col">
                                        <span className="font-semibold text-foreground">{inv.full_number}</span>
                                        <span className="text-[11px] text-muted-foreground">{inv.code}</span>
                                    </div>
                                </td>
                                <td className="px-3 py-2.5 tabular-nums text-muted-foreground">{inv.invoice_date}</td>
                                <td className="px-3 py-2.5 tabular-nums text-muted-foreground">{inv.due_date}</td>
                                <td className="px-3 py-2.5 tabular-nums">
                                    {inv.is_overdue ? (
                                        <span className="inline-flex items-center gap-1 font-semibold text-destructive">
                                            <AlertTriangle className="h-3 w-3" />
                                            {inv.days_overdue} d vencida
                                        </span>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            {Math.abs(inv.days_overdue)} d por vencer
                                        </span>
                                    )}
                                </td>
                                <td className="px-3 py-2.5 text-muted-foreground">{inv.salesperson_name}</td>
                                <td className="px-3 py-2.5 text-right tabular-nums text-muted-foreground">
                                    {inv.currency} {formatAmount(inv.total)}
                                </td>
                                <td className="px-3 py-2.5 text-right tabular-nums text-muted-foreground">
                                    {inv.currency} {formatAmount(inv.paid_amount)}
                                </td>
                                <td className="px-3 py-2.5 text-right font-semibold tabular-nums text-foreground">
                                    {inv.currency} {formatAmount(inv.balance)}
                                </td>
                                <td className="px-3 py-2.5 text-right font-medium tabular-nums text-emerald-600 dark:text-emerald-400">
                                    ${formatAmount(inv.balance_usd)}
                                </td>
                                <td className="px-3 py-2.5 text-right font-medium tabular-nums text-muted-foreground">
                                    Bs. {formatAmount(inv.balance_ves)}
                                </td>
                                <td className="px-3 py-2.5 text-center">
                                    {inv.payment_status === 'overdue' ? (
                                        <Badge variant="destructive" className="text-[10px] uppercase">
                                            Vencida
                                        </Badge>
                                    ) : inv.payment_status === 'partial' ? (
                                        <Badge variant="outline" className="border-amber-500/50 text-amber-600 text-[10px] uppercase">
                                            Parcial
                                        </Badge>
                                    ) : (
                                        <Badge variant="secondary" className="text-[10px] uppercase">
                                            Pendiente
                                        </Badge>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Paginación interna de facturas de este cliente */}
            <Pagination
                meta={{
                    total: invoices.length,
                    limit: subLimit,
                    offset: subOffset,
                    has_more: invoices.length > subOffset + subLimit,
                }}
                onPageChange={(newOffset) => setSubOffset(newOffset)}
                label="factura"
                labelPlural="facturas"
                className="mt-2 border-t-0 px-2 py-2"
            />
        </div>
    );
}

export default function AccountsReceivableReport({
    consulted = false,
    rows,
    summary,
    meta,
    filters: initialFilters,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany?.id ?? '';

    const [expandedClients, setExpandedClients] = useState<Record<string, boolean>>({});
    const [filters, setFilters] = useState<FilterData>({ ...initialFilters, consulted });

    const toggleClient = (clientId: string) => {
        setExpandedClients((prev) => ({
            ...prev,
            [clientId]: !prev[clientId],
        }));
    };

    const toggleAll = () => {
        const anyClosed = rows.some((r) => !expandedClients[r.client.id]);
        const next: Record<string, boolean> = {};
        rows.forEach((r) => {
            next[r.client.id] = anyClosed;
        });
        setExpandedClients(next);
    };

    const applyFilters = (newFilters: Partial<FilterData>) => {
        const next = { ...filters, ...newFilters, consulted: true, offset: newFilters.offset ?? 0 };
        setFilters(next);
        router.get(`/${companyId}/reports/accounts-receivable`, next as unknown as Record<string, string>, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        const defaultFilters: FilterData = {
            consulted: false,
            as_of_date: new Date().toISOString().split('T')[0],
            client_id: null,
            salesperson_id: null,
            payment_status: 'all',
            limit: 15,
            offset: 0,
        };
        setFilters(defaultFilters);
        router.get(`/${companyId}/reports/accounts-receivable`);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Reportes',
            href: `/${companyId}/reports/accounts-receivable`,
        },
        {
            title: 'Cuentas por Cobrar',
            href: `/${companyId}/reports/accounts-receivable`,
        },
    ];

    const clientFilterOptions: OptionType[] = [
        { value: 'all', label: 'Todos los clientes' },
        ...options.clients,
    ];

    const salespersonFilterOptions: OptionType[] = [
        { value: 'all', label: 'Todos los vendedores' },
        ...options.salespersons,
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reporte de Cuentas por Cobrar (Cartera de Clientes)" />

            <div className="mx-auto w-full max-w-7xl space-y-6 p-6 pb-16">
                {/* Header */}
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Cuentas por Cobrar (Cartera de Clientes)
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Análisis jerárquico de saldos vivos, antigüedad de cartera y facturas pendientes por cliente al corte.
                        </p>
                    </div>
                    {consulted && rows.length > 0 && (
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={toggleAll}>
                                {rows.some((r) => !expandedClients[r.client.id]) ? 'Desplegar todas' : 'Plegar todas'}
                            </Button>
                        </div>
                    )}
                </div>

                {/* Filter Bar */}
                <Card className="shadow-sm">
                    <CardContent className="p-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="as_of_date" className="text-xs font-semibold text-muted-foreground">
                                    Fecha de Corte
                                </Label>
                                <Input
                                    id="as_of_date"
                                    type="date"
                                    value={filters.as_of_date}
                                    onChange={(e) => setFilters({ ...filters, as_of_date: e.target.value })}
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold text-muted-foreground">Cliente</Label>
                                <Select2
                                    options={clientFilterOptions}
                                    value={
                                        clientFilterOptions.find((o) => o.value === (filters.client_id ?? 'all')) ?? null
                                    }
                                    onChange={(opt) =>
                                        setFilters({
                                            ...filters,
                                            client_id: opt && opt.value !== 'all' ? opt.value : null,
                                        })
                                    }
                                    placeholder="Todos los clientes"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold text-muted-foreground">Vendedor</Label>
                                <Select2
                                    options={salespersonFilterOptions}
                                    value={
                                        salespersonFilterOptions.find((o) => o.value === (filters.salesperson_id ?? 'all')) ?? null
                                    }
                                    onChange={(opt) =>
                                        setFilters({
                                            ...filters,
                                            salesperson_id: opt && opt.value !== 'all' ? opt.value : null,
                                        })
                                    }
                                    placeholder="Todos los vendedores"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold text-muted-foreground">Estado de Vencimiento</Label>
                                <Select2
                                    options={PAYMENT_STATUS_OPTIONS}
                                    value={
                                        PAYMENT_STATUS_OPTIONS.find((o) => o.value === (filters.payment_status ?? 'all')) ?? null
                                    }
                                    onChange={(opt) =>
                                        setFilters({
                                            ...filters,
                                            payment_status: opt && opt.value !== 'all' ? opt.value : 'all',
                                        })
                                    }
                                    placeholder="Todas las deudas"
                                />
                            </div>
                        </div>

                        <div className="mt-4 flex items-center justify-between border-t pt-3">
                            <span className="text-xs text-muted-foreground">
                                Tasa referencial oficial: <strong>Bs. {formatAmount(summary.exchange_rate)} / USD</strong>
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

                {/* KPI Executive Summary Cards (solo visibles tras consultar) */}
                {consulted && (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        <Card className="border-l-4 border-l-primary shadow-sm">
                            <CardContent className="p-4">
                                <span className="text-xs font-semibold uppercase text-muted-foreground">Cartera Bruta</span>
                                <div className="mt-1 text-xl font-bold tracking-tight text-foreground tabular-nums">
                                    ${formatAmount(summary.total_gross_debt)}
                                </div>
                                <span className="text-[11px] text-muted-foreground">
                                    {summary.total_invoices} facturas emitidas
                                </span>
                            </CardContent>
                        </Card>

                        <Card className="border-l-4 border-l-amber-500 shadow-sm">
                            <CardContent className="p-4">
                                <span className="text-xs font-semibold uppercase text-muted-foreground">(-) Anticipos</span>
                                <div className="mt-1 text-xl font-bold tracking-tight text-amber-600 dark:text-amber-400 tabular-nums">
                                    ${formatAmount(summary.total_advances)}
                                </div>
                                <span className="text-[11px] text-muted-foreground">Disponibles a favor</span>
                            </CardContent>
                        </Card>

                        <Card className="border-l-4 border-l-blue-500 shadow-sm">
                            <CardContent className="p-4">
                                <span className="text-xs font-semibold uppercase text-muted-foreground">(-) Notas Crédito</span>
                                <div className="mt-1 text-xl font-bold tracking-tight text-blue-600 dark:text-blue-400 tabular-nums">
                                    ${formatAmount(summary.total_credit_notes)}
                                </div>
                                <span className="text-[11px] text-muted-foreground">Saldos abiertos</span>
                            </CardContent>
                        </Card>

                        <Card className="border-l-4 border-l-emerald-600 shadow-sm bg-emerald-500/5">
                            <CardContent className="p-4">
                                <span className="text-xs font-bold uppercase text-emerald-700 dark:text-emerald-400">Deuda Neta Total</span>
                                <div className="mt-1 text-xl font-extrabold tracking-tight text-emerald-700 dark:text-emerald-400 tabular-nums">
                                    ${formatAmount(summary.total_net_debt)}
                                </div>
                                <span className="text-[11px] text-muted-foreground">Exigible real</span>
                            </CardContent>
                        </Card>

                        <Card className="border-l-4 border-l-sky-500 shadow-sm">
                            <CardContent className="p-4">
                                <span className="text-xs font-semibold uppercase text-muted-foreground">Corriente</span>
                                <div className="mt-1 text-xl font-bold tracking-tight text-sky-600 dark:text-sky-400 tabular-nums">
                                    ${formatAmount(summary.total_current)}
                                </div>
                                <span className="text-[11px] text-muted-foreground">Por vencer</span>
                            </CardContent>
                        </Card>

                        <Card className="border-l-4 border-l-destructive shadow-sm bg-destructive/5">
                            <CardContent className="p-4">
                                <span className="text-xs font-bold uppercase text-destructive">En Mora</span>
                                <div className="mt-1 text-xl font-extrabold tracking-tight text-destructive tabular-nums">
                                    ${formatAmount(summary.total_overdue)}
                                </div>
                                <span className="text-[11px] text-muted-foreground">Vencidas al corte</span>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Master Table o Estado Vacío Pre-Consulta */}
                <Card className="shadow-sm">
                    {!consulted ? (
                        <div className="flex flex-col items-center justify-center p-12 text-center">
                            <div className="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary mb-4">
                                <Search className="h-7 w-7" />
                            </div>
                            <h3 className="text-base font-semibold text-foreground">Reporte listo para consultar</h3>
                            <p className="mt-1 max-w-md text-sm text-muted-foreground">
                                Seleccione la fecha de corte y los filtros deseados arriba, y presione «Consultar» para cargar la información.
                            </p>
                            <Button className="mt-4" onClick={() => applyFilters({ consulted: true })}>
                                <Search className="mr-2 h-4 w-4" />
                                Consultar
                            </Button>
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="border-b bg-muted/60 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                        <tr>
                                            <th className="w-10 px-3 py-3 text-center">#</th>
                                            <th className="px-4 py-3">Cliente / RIF</th>
                                            <th className="px-3 py-3 text-right">Límite</th>
                                            <th className="px-3 py-3 text-right">Corriente</th>
                                            <th className="px-3 py-3 text-right">1 a 30 d</th>
                                            <th className="px-3 py-3 text-right">31 a 60 d</th>
                                            <th className="px-3 py-3 text-right">61 a 90 d</th>
                                            <th className="px-3 py-3 text-right">+90 d</th>
                                            <th className="px-3 py-3 text-right">Deuda Bruta</th>
                                            <th className="px-3 py-3 text-right">(-) Anticipos</th>
                                            <th className="px-3 py-3 text-right">(-) N/Crédito</th>
                                            <th className="px-4 py-3 text-right font-bold text-foreground">Deuda Neta</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {rows.length === 0 ? (
                                            <tr>
                                                <td colSpan={12} className="py-12 text-center text-sm text-muted-foreground">
                                                    No se encontraron clientes con facturas pendientes para los filtros seleccionados.
                                                </td>
                                            </tr>
                                        ) : (
                                            rows.map((row) => {
                                                const isExpanded = !!expandedClients[row.client.id];
                                                return (
                                                    <Fragment key={row.client.id}>
                                                        <tr
                                                            onClick={() => toggleClient(row.client.id)}
                                                            className={`cursor-pointer transition-colors hover:bg-muted/40 ${
                                                                isExpanded ? 'bg-muted/20 font-medium' : ''
                                                            }`}
                                                        >
                                                            <td className="px-3 py-3 text-center">
                                                                <button
                                                                    type="button"
                                                                    className="inline-flex h-6 w-6 items-center justify-center rounded-md hover:bg-muted"
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        toggleClient(row.client.id);
                                                                    }}
                                                                >
                                                                    {isExpanded ? (
                                                                        <ChevronDown className="h-4 w-4 text-primary" />
                                                                    ) : (
                                                                        <ChevronRight className="h-4 w-4 text-muted-foreground" />
                                                                    )}
                                                                </button>
                                                            </td>
                                                            <td className="px-4 py-3">
                                                                <div className="flex flex-col">
                                                                    <div className="flex items-center gap-2">
                                                                        <span className="font-semibold text-foreground">
                                                                            {row.client.name}
                                                                        </span>
                                                                        <Badge variant="outline" className="text-[10px] tabular-nums">
                                                                            {row.invoices_count} fact.
                                                                        </Badge>
                                                                    </div>
                                                                    <span className="text-xs text-muted-foreground tabular-nums">
                                                                        {row.client.tax_number} · {row.client.code}
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td className="px-3 py-3 text-right tabular-nums text-muted-foreground">
                                                                ${formatAmount(row.client.credit_limit)}
                                                            </td>
                                                            <td className="px-3 py-3 text-right tabular-nums text-foreground">
                                                                ${formatAmount(row.aging.current)}
                                                            </td>
                                                            <td className={`px-3 py-3 text-right tabular-nums ${row.aging.days_1_30 > 0 ? 'text-amber-600 font-semibold' : 'text-muted-foreground'}`}>
                                                                ${formatAmount(row.aging.days_1_30)}
                                                            </td>
                                                            <td className={`px-3 py-3 text-right tabular-nums ${row.aging.days_31_60 > 0 ? 'text-amber-600 font-semibold' : 'text-muted-foreground'}`}>
                                                                ${formatAmount(row.aging.days_31_60)}
                                                            </td>
                                                            <td className={`px-3 py-3 text-right tabular-nums ${row.aging.days_61_90 > 0 ? 'text-destructive font-semibold' : 'text-muted-foreground'}`}>
                                                                ${formatAmount(row.aging.days_61_90)}
                                                            </td>
                                                            <td className={`px-3 py-3 text-right tabular-nums ${row.aging.days_over_90 > 0 ? 'text-destructive font-bold' : 'text-muted-foreground'}`}>
                                                                ${formatAmount(row.aging.days_over_90)}
                                                            </td>
                                                            <td className="px-3 py-3 text-right font-semibold tabular-nums text-foreground">
                                                                ${formatAmount(row.gross_debt)}
                                                            </td>
                                                            <td className="px-3 py-3 text-right tabular-nums text-amber-600 dark:text-amber-400">
                                                                {row.advances_balance > 0 ? `-$${formatAmount(row.advances_balance)}` : '$0,00'}
                                                            </td>
                                                            <td className="px-3 py-3 text-right tabular-nums text-blue-600 dark:text-blue-400">
                                                                {row.credit_notes_balance > 0 ? `-$${formatAmount(row.credit_notes_balance)}` : '$0,00'}
                                                            </td>
                                                            <td className="px-4 py-3 text-right font-extrabold tabular-nums text-emerald-600 dark:text-emerald-400">
                                                                ${formatAmount(row.net_debt)}
                                                            </td>
                                                        </tr>

                                                        {isExpanded && (
                                                            <tr className="bg-muted/10">
                                                                <td colSpan={12} className="px-4 py-3">
                                                                    <ClientSubtable invoices={row.invoices} />
                                                                </td>
                                                            </tr>
                                                        )}
                                                    </Fragment>
                                                );
                                            })
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            <Pagination
                                meta={meta}
                                onPageChange={(newOffset) => {
                                    applyFilters({ offset: newOffset });
                                }}
                                label="cliente con deuda"
                                labelPlural="clientes con deuda"
                            />
                        </>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
