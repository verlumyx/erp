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
    FileText,
    Truck,
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
    supplier_invoice_series?: string | null;
    supplier_invoice_number?: string | null;
    full_number: string;
    invoice_date: string;
    received_date?: string | null;
    due_date: string;
    days_overdue: number;
    is_overdue: boolean;
    currency: string;
    total: number;
    withholding_amount: number;
    paid_amount: number;
    balance: number;
    balance_usd: number;
    balance_ves: number;
    payment_status: string;
}

interface SupplierRow {
    supplier: {
        id: string;
        code: string;
        name: string;
        legal_name?: string | null;
        tax_number: string;
        document_type: string;
        document_number: string;
        supplier_type_name: string;
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
    total_suppliers: number;
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
    supplier_id?: string | null;
    supplier_type_id?: string | null;
    payment_status?: string | null;
    currency_view?: string;
    limit: number;
    offset: number;
}

interface OptionsData {
    suppliers: { value: string; label: string }[];
    supplier_types: { value: string; label: string }[];
}

interface Props {
    consulted: boolean;
    rows: SupplierRow[];
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

function SupplierSubtable({ invoices }: { invoices: InvoiceRow[] }) {
    const [subOffset, setSubOffset] = useState(0);
    const subLimit = 5;
    const currentInvoices = invoices.slice(subOffset, subOffset + subLimit);

    return (
        <div className="rounded-lg border bg-card p-4 shadow-sm">
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <FileText className="h-4 w-4 text-primary" />
                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Facturas de compra pendientes por pagar
                    </span>
                    <Badge variant="secondary" className="text-xs font-semibold">
                        {invoices.length} {invoices.length === 1 ? 'factura' : 'facturas'}
                    </Badge>
                </div>
            </div>

            <div className="overflow-x-auto rounded border">
                <table className="w-full text-left text-xs">
                    <thead className="border-b bg-muted/60 font-semibold text-muted-foreground">
                        <tr>
                            <th className="px-3 py-2">Factura Proveedor / Código ERP</th>
                            <th className="px-3 py-2">Emisión</th>
                            <th className="px-3 py-2">Recepción</th>
                            <th className="px-3 py-2">Vencimiento</th>
                            <th className="px-3 py-2">Mora / Estado</th>
                            <th className="px-3 py-2 text-right">Total Factura</th>
                            <th className="px-3 py-2 text-right">Retenciones</th>
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
                                <td className="px-3 py-2.5 tabular-nums text-muted-foreground">{inv.received_date ?? '—'}</td>
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
                                <td className="px-3 py-2.5 text-right tabular-nums text-muted-foreground">
                                    {inv.currency} {formatAmount(inv.total)}
                                </td>
                                <td className="px-3 py-2.5 text-right tabular-nums text-muted-foreground">
                                    {inv.withholding_amount > 0 ? `${inv.currency} ${formatAmount(inv.withholding_amount)}` : '—'}
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

            {/* Paginación interna de facturas del proveedor */}
            <Pagination
                meta={{
                    total: invoices.length,
                    limit: subLimit,
                    offset: subOffset,
                    has_more: invoices.length > subOffset + subLimit,
                }}
                onPageChange={(newOffset) => setSubOffset(newOffset)}
                label="factura de compra"
                labelPlural="facturas de compra"
                className="mt-2 border-t-0 px-2 py-2"
            />
        </div>
    );
}

export default function AccountsPayableReport({
    consulted = false,
    rows,
    summary,
    meta,
    filters: initialFilters,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany?.id ?? '';

    const [expandedSuppliers, setExpandedSuppliers] = useState<Record<string, boolean>>({});
    const [filters, setFilters] = useState<FilterData>({ ...initialFilters, consulted });

    const toggleSupplier = (supplierId: string) => {
        setExpandedSuppliers((prev) => ({
            ...prev,
            [supplierId]: !prev[supplierId],
        }));
    };

    const toggleAll = () => {
        const anyClosed = rows.some((r) => !expandedSuppliers[r.supplier.id]);
        const next: Record<string, boolean> = {};
        rows.forEach((r) => {
            next[r.supplier.id] = anyClosed;
        });
        setExpandedSuppliers(next);
    };

    const applyFilters = (newFilters: Partial<FilterData>) => {
        const next = { ...filters, ...newFilters, consulted: true, offset: newFilters.offset ?? 0 };
        setFilters(next);
        router.get(`/${companyId}/reports/accounts-payable`, next as unknown as Record<string, string>, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        const defaultFilters: FilterData = {
            consulted: false,
            as_of_date: new Date().toISOString().split('T')[0],
            supplier_id: null,
            supplier_type_id: null,
            payment_status: 'all',
            limit: 15,
            offset: 0,
        };
        setFilters(defaultFilters);
        router.get(`/${companyId}/reports/accounts-payable`);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Reportes',
            href: `/${companyId}/reports/accounts-payable`,
        },
        {
            title: 'Cuentas por Pagar',
            href: `/${companyId}/reports/accounts-payable`,
        },
    ];

    const supplierFilterOptions: OptionType[] = [
        { value: 'all', label: 'Todos los proveedores' },
        ...options.suppliers,
    ];

    const supplierTypeFilterOptions: OptionType[] = [
        { value: 'all', label: 'Todas las clasificaciones' },
        ...options.supplier_types,
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reporte de Cuentas por Pagar (Pasivos con Proveedores)" />

            <div className="mx-auto w-full max-w-7xl space-y-6 p-6 pb-16">
                {/* Header */}
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Cuentas por Pagar (Pasivos con Proveedores)
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Control jerárquico de compromisos y obligaciones de pago, vencimiento de facturas y anticipos entregados.
                        </p>
                    </div>
                    {consulted && rows.length > 0 && (
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={toggleAll}>
                                {rows.some((r) => !expandedSuppliers[r.supplier.id]) ? 'Desplegar todos' : 'Plegar todos'}
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
                                <Label className="text-xs font-semibold text-muted-foreground">Proveedor</Label>
                                <Select2
                                    options={supplierFilterOptions}
                                    value={
                                        supplierFilterOptions.find((o) => o.value === (filters.supplier_id ?? 'all')) ?? null
                                    }
                                    onChange={(opt) =>
                                        setFilters({
                                            ...filters,
                                            supplier_id: opt && opt.value !== 'all' ? opt.value : null,
                                        })
                                    }
                                    placeholder="Todos los proveedores"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold text-muted-foreground">Tipo de Proveedor</Label>
                                <Select2
                                    options={supplierTypeFilterOptions}
                                    value={
                                        supplierTypeFilterOptions.find((o) => o.value === (filters.supplier_type_id ?? 'all')) ?? null
                                    }
                                    onChange={(opt) =>
                                        setFilters({
                                            ...filters,
                                            supplier_type_id: opt && opt.value !== 'all' ? opt.value : null,
                                        })
                                    }
                                    placeholder="Todas las clasificaciones"
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
                                <span className="text-xs font-semibold uppercase text-muted-foreground">Pasivo Bruto</span>
                                <div className="mt-1 text-xl font-bold tracking-tight text-foreground tabular-nums">
                                    ${formatAmount(summary.total_gross_debt)}
                                </div>
                                <span className="text-[11px] text-muted-foreground">
                                    {summary.total_invoices} facturas por pagar
                                </span>
                            </CardContent>
                        </Card>

                        <Card className="border-l-4 border-l-amber-500 shadow-sm">
                            <CardContent className="p-4">
                                <span className="text-xs font-semibold uppercase text-muted-foreground">(-) Anticipos</span>
                                <div className="mt-1 text-xl font-bold tracking-tight text-amber-600 dark:text-amber-400 tabular-nums">
                                    ${formatAmount(summary.total_advances)}
                                </div>
                                <span className="text-[11px] text-muted-foreground">Entregados a favor</span>
                            </CardContent>
                        </Card>

                        <Card className="border-l-4 border-l-blue-500 shadow-sm">
                            <CardContent className="p-4">
                                <span className="text-xs font-semibold uppercase text-muted-foreground">(-) Notas Crédito</span>
                                <div className="mt-1 text-xl font-bold tracking-tight text-blue-600 dark:text-blue-400 tabular-nums">
                                    ${formatAmount(summary.total_credit_notes)}
                                </div>
                                <span className="text-[11px] text-muted-foreground">Por cruzar</span>
                            </CardContent>
                        </Card>

                        <Card className="border-l-4 border-l-indigo-600 shadow-sm bg-indigo-500/5">
                            <CardContent className="p-4">
                                <span className="text-xs font-bold uppercase text-indigo-700 dark:text-indigo-400">Total Neto a Pagar</span>
                                <div className="mt-1 text-xl font-extrabold tracking-tight text-indigo-700 dark:text-indigo-400 tabular-nums">
                                    ${formatAmount(summary.total_net_debt)}
                                </div>
                                <span className="text-[11px] text-muted-foreground">Compromiso real</span>
                            </CardContent>
                        </Card>

                        <Card className="border-l-4 border-l-sky-500 shadow-sm">
                            <CardContent className="p-4">
                                <span className="text-xs font-semibold uppercase text-muted-foreground">Por Vencer</span>
                                <div className="mt-1 text-xl font-bold tracking-tight text-sky-600 dark:text-sky-400 tabular-nums">
                                    ${formatAmount(summary.total_current)}
                                </div>
                                <span className="text-[11px] text-muted-foreground">En plazo comercial</span>
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
                                <Label className="text-xs font-semibold text-muted-foreground">Proveedor</Label>
                                <Select2
                                    options={supplierFilterOptions}
                                    value={
                                        supplierFilterOptions.find((o) => o.value === (filters.supplier_id ?? 'all')) ?? null
                                    }
                                    onChange={(opt) =>
                                        setFilters({
                                            ...filters,
                                            supplier_id: opt && opt.value !== 'all' ? opt.value : null,
                                        })
                                    }
                                    placeholder="Todos los proveedores"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold text-muted-foreground">Tipo de Proveedor</Label>
                                <Select2
                                    options={supplierTypeFilterOptions}
                                    value={
                                        supplierTypeFilterOptions.find((o) => o.value === (filters.supplier_type_id ?? 'all')) ?? null
                                    }
                                    onChange={(opt) =>
                                        setFilters({
                                            ...filters,
                                            supplier_type_id: opt && opt.value !== 'all' ? opt.value : null,
                                        })
                                    }
                                    placeholder="Todas las clasificaciones"
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
                                <Button size="sm" onClick={() => applyFilters(filters)}>
                                    <Search className="mr-1.5 h-3.5 w-3.5" />
                                    Consultar
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

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
                                            <th className="px-4 py-3">Proveedor / RIF</th>
                                            <th className="px-3 py-3">Tipo</th>
                                            <th className="px-3 py-3 text-right">Corriente</th>
                                            <th className="px-3 py-3 text-right">1 a 30 d</th>
                                            <th className="px-3 py-3 text-right">31 a 60 d</th>
                                            <th className="px-3 py-3 text-right">61 a 90 d</th>
                                            <th className="px-3 py-3 text-right">+90 d</th>
                                            <th className="px-3 py-3 text-right">Pasivo Bruto</th>
                                            <th className="px-3 py-3 text-right">(-) Anticipos</th>
                                            <th className="px-3 py-3 text-right">(-) N/Crédito</th>
                                            <th className="px-4 py-3 text-right font-bold text-foreground">Neto a Pagar</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {rows.length === 0 ? (
                                            <tr>
                                                <td colSpan={12} className="py-12 text-center text-sm text-muted-foreground">
                                                    No se encontraron proveedores con obligaciones pendientes para los filtros seleccionados.
                                                </td>
                                            </tr>
                                        ) : (
                                            rows.map((row) => {
                                                const isExpanded = !!expandedSuppliers[row.supplier.id];
                                                return (
                                                    <Fragment key={row.supplier.id}>
                                                        {/* Fila principal del proveedor con flecha al lado izquierdo */}
                                                        <tr
                                                            onClick={() => toggleSupplier(row.supplier.id)}
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
                                                                        toggleSupplier(row.supplier.id);
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
                                                                            {row.supplier.name}
                                                                        </span>
                                                                        <Badge variant="outline" className="text-[10px] tabular-nums">
                                                                            {row.invoices_count} fact.
                                                                        </Badge>
                                                                    </div>
                                                                    <span className="text-xs text-muted-foreground tabular-nums">
                                                                        {row.supplier.tax_number} · {row.supplier.code}
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td className="px-3 py-3 text-xs text-muted-foreground">
                                                                {row.supplier.supplier_type_name}
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
                                                            <td className="px-4 py-3 text-right font-extrabold tabular-nums text-indigo-600 dark:text-indigo-400">
                                                                ${formatAmount(row.net_debt)}
                                                            </td>
                                                        </tr>

                                                        {/* Panel Desplegable: Subtabla de facturas de compra pendientes con paginación interna */}
                                                        {isExpanded && (
                                                            <tr className="bg-muted/10">
                                                                <td colSpan={12} className="px-4 py-3">
                                                                    <SupplierSubtable invoices={row.invoices} />
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

                            {/* Paginación de la tabla principal de proveedores */}
                            <Pagination
                                meta={meta}
                                onPageChange={(newOffset) => {
                                    applyFilters({ offset: newOffset });
                                }}
                                label="proveedor con deuda"
                                labelPlural="proveedores con deuda"
                            />
                        </>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
