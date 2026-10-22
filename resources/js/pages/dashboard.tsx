import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    CheckCircle2,
    Clock,
    DollarSign,
    ExternalLink,
    FilePlus,
    Package,
    Receipt,
    ShoppingCart,
    TrendingUp,
    Wallet,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import clientCollections from '@/routes/client-collections';
import company from '@/routes/company';
import items from '@/routes/items';
import purchaseInvoices from '@/routes/purchase-invoices';
import salesInvoices from '@/routes/sales-invoices';
import salesOrders from '@/routes/sales-orders';
import { type BreadcrumbItem } from '@/types';
import type { Configuration } from '@/types/configuration';

export interface DashboardStats {
    kpis: {
        todaySales: {
            total: number;
            totalVes: number;
            count: number;
        };
        todayCollections: {
            total: number;
            totalVes: number;
            count: number;
        };
        receivables: {
            balance: number;
            count: number;
        };
        payables: {
            balance: number;
            count: number;
        };
    };
    alerts: {
        overdueInvoicesCount: number;
        overdueInvoicesBalance: number;
        lowStockItemsCount: number;
        pendingOrdersCount: number;
    };
    recentInvoices: Array<{
        id: string;
        code: string;
        clientName: string;
        total: number;
        currency: string;
        status: string;
        paymentStatus: string;
        invoiceDate: string;
    }>;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    configuration?: Configuration | null;
    stats?: DashboardStats;
    [key: string]: unknown;
}

const defaultStats: DashboardStats = {
    kpis: {
        todaySales: { total: 0, totalVes: 0, count: 0 },
        todayCollections: { total: 0, totalVes: 0, count: 0 },
        receivables: { balance: 0, count: 0 },
        payables: { balance: 0, count: 0 },
    },
    alerts: {
        overdueInvoicesCount: 0,
        overdueInvoicesBalance: 0,
        lowStockItemsCount: 0,
        pendingOrdersCount: 0,
    },
    recentInvoices: [],
};

const formatAmount = (value: number): string =>
    new Intl.NumberFormat('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);

const getPaymentStatusBadge = (status: string) => {
    switch (status) {
        case 'paid':
            return (
                <Badge className="border-emerald-200 bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:border-emerald-800 dark:text-emerald-400">
                    Cobrada
                </Badge>
            );
        case 'partial':
            return (
                <Badge className="border-blue-200 bg-blue-500/15 text-blue-700 hover:bg-blue-500/25 dark:border-blue-800 dark:text-blue-400">
                    Parcial
                </Badge>
            );
        case 'overdue':
            return (
                <Badge className="border-destructive/20 bg-destructive/15 text-destructive hover:bg-destructive/25">
                    Vencida
                </Badge>
            );
        case 'pending':
        default:
            return (
                <Badge
                    variant="outline"
                    className="border-amber-200 bg-amber-500/10 text-amber-700 dark:border-amber-800 dark:text-amber-400"
                >
                    Pendiente
                </Badge>
            );
    }
};

export default function Dashboard() {
    const {
        currentCompany,
        configuration,
        stats = defaultStats,
    } = usePage<PageProps>().props;

    const companyId = currentCompany?.id;
    const baseCurrency = configuration?.base_currency ?? '$';

    const breadcrumbs: BreadcrumbItem[] = companyId
        ? [{ title: 'Resumen', href: company.dashboard(companyId).url }]
        : [];

    const hoy = new Date();
    const fechaLegible = hoy.toLocaleDateString('es-ES', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    const hasAlerts =
        stats.alerts.overdueInvoicesCount > 0 ||
        stats.alerts.lowStockItemsCount > 0 ||
        stats.alerts.pendingOrdersCount > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Resumen" />

            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6 pb-14">
                {/* Cabecera Principal */}
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-extrabold tracking-tight sm:text-3xl">
                            Resumen Operativo
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground capitalize">
                            {fechaLegible}
                        </p>
                    </div>

                    {currentCompany && (
                        <div className="flex items-center gap-2">
                            <Badge
                                variant="outline"
                                className="px-3 py-1 text-xs font-semibold"
                            >
                                Moneda Base: {baseCurrency}
                            </Badge>
                        </div>
                    )}
                </div>

                {/* 1. Las 4 Tarjetas Clave (KPIs) */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {/* Ventas de Hoy */}
                    <Card className="py-4">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Ventas de Hoy
                            </CardTitle>
                            <div className="rounded-lg bg-emerald-500/10 p-2 text-emerald-600 dark:text-emerald-400">
                                <TrendingUp className="size-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold tracking-tight">
                                {baseCurrency}{' '}
                                {formatAmount(stats.kpis.todaySales.total)}
                            </div>
                            <div className="mt-1 flex items-center justify-between text-xs text-muted-foreground">
                                <span>
                                    {stats.kpis.todaySales.count}{' '}
                                    {stats.kpis.todaySales.count === 1
                                        ? 'factura'
                                        : 'facturas'}
                                </span>
                                {stats.kpis.todaySales.totalVes > 0 && (
                                    <span className="font-medium text-foreground/80">
                                        Bs.{' '}
                                        {formatAmount(
                                            stats.kpis.todaySales.totalVes,
                                        )}
                                    </span>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Cobrado Hoy */}
                    <Card className="py-4">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Cobrado Hoy
                            </CardTitle>
                            <div className="rounded-lg bg-blue-500/10 p-2 text-blue-600 dark:text-blue-400">
                                <DollarSign className="size-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold tracking-tight">
                                {baseCurrency}{' '}
                                {formatAmount(
                                    stats.kpis.todayCollections.total,
                                )}
                            </div>
                            <div className="mt-1 flex items-center justify-between text-xs text-muted-foreground">
                                <span>
                                    {stats.kpis.todayCollections.count}{' '}
                                    {stats.kpis.todayCollections.count === 1
                                        ? 'cobro'
                                        : 'cobros'}
                                </span>
                                {stats.kpis.todayCollections.totalVes > 0 && (
                                    <span className="font-medium text-foreground/80">
                                        Bs.{' '}
                                        {formatAmount(
                                            stats.kpis.todayCollections
                                                .totalVes,
                                        )}
                                    </span>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Cuentas por Cobrar */}
                    <Card className="py-4">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Por Cobrar (Clientes)
                            </CardTitle>
                            <div className="rounded-lg bg-indigo-500/10 p-2 text-indigo-600 dark:text-indigo-400">
                                <Clock className="size-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold tracking-tight">
                                {baseCurrency}{' '}
                                {formatAmount(stats.kpis.receivables.balance)}
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {stats.kpis.receivables.count} facturas
                                pendientes de cobro
                            </p>
                        </CardContent>
                    </Card>

                    {/* Cuentas por Pagar */}
                    <Card className="py-4">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Por Pagar (Proveedores)
                            </CardTitle>
                            <div className="rounded-lg bg-amber-500/10 p-2 text-amber-600 dark:text-amber-400">
                                <Wallet className="size-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold tracking-tight">
                                {baseCurrency}{' '}
                                {formatAmount(stats.kpis.payables.balance)}
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {stats.kpis.payables.count} facturas pendientes
                                de pago
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* 2. Semáforo de Pendientes (Atención Requerida) */}
                <div>
                    <h2 className="mb-3 text-lg font-bold tracking-tight">
                        Atención Requerida
                    </h2>

                    {hasAlerts ? (
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                            {/* Facturas Vencidas */}
                            {stats.alerts.overdueInvoicesCount > 0 ? (
                                <Card className="border-destructive/30 bg-destructive/5 py-3">
                                    <CardContent className="flex flex-col justify-between gap-3 p-4">
                                        <div className="flex items-start gap-3">
                                            <div className="rounded-md bg-destructive/15 p-2 text-destructive">
                                                <AlertTriangle className="size-5" />
                                            </div>
                                            <div>
                                                <p className="font-semibold text-destructive">
                                                    {
                                                        stats.alerts
                                                            .overdueInvoicesCount
                                                    }{' '}
                                                    {stats.alerts
                                                        .overdueInvoicesCount ===
                                                    1
                                                        ? 'Factura Vencida'
                                                        : 'Facturas Vencidas'}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Total vencido:{' '}
                                                    {baseCurrency}{' '}
                                                    {formatAmount(
                                                        stats.alerts
                                                            .overdueInvoicesBalance,
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                        {companyId && (
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                                className="w-full justify-between border-destructive/20 text-xs hover:bg-destructive/10"
                                            >
                                                <Link
                                                    href={
                                                        salesInvoices.index(
                                                            companyId,
                                                        ).url
                                                    }
                                                >
                                                    Gestionar Cobros
                                                    <ArrowRight className="size-3.5" />
                                                </Link>
                                            </Button>
                                        )}
                                    </CardContent>
                                </Card>
                            ) : null}

                            {/* Stock Bajo */}
                            {stats.alerts.lowStockItemsCount > 0 ? (
                                <Card className="border-amber-500/30 bg-amber-500/5 py-3">
                                    <CardContent className="flex flex-col justify-between gap-3 p-4">
                                        <div className="flex items-start gap-3">
                                            <div className="rounded-md bg-amber-500/15 p-2 text-amber-600 dark:text-amber-400">
                                                <Package className="size-5" />
                                            </div>
                                            <div>
                                                <p className="font-semibold text-amber-700 dark:text-amber-400">
                                                    {
                                                        stats.alerts
                                                            .lowStockItemsCount
                                                    }{' '}
                                                    {stats.alerts
                                                        .lowStockItemsCount ===
                                                    1
                                                        ? 'Artículo en Mínimo'
                                                        : 'Artículos en Mínimo'}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Stock igual o por debajo del
                                                    límite de seguridad
                                                </p>
                                            </div>
                                        </div>
                                        {companyId && (
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                                className="w-full justify-between border-amber-500/20 text-xs hover:bg-amber-500/10"
                                            >
                                                <Link
                                                    href={
                                                        items.index(companyId)
                                                            .url
                                                    }
                                                >
                                                    Revisar Inventario
                                                    <ArrowRight className="size-3.5" />
                                                </Link>
                                            </Button>
                                        )}
                                    </CardContent>
                                </Card>
                            ) : null}

                            {/* Pedidos Pendientes */}
                            {stats.alerts.pendingOrdersCount > 0 ? (
                                <Card className="border-blue-500/30 bg-blue-500/5 py-3">
                                    <CardContent className="flex flex-col justify-between gap-3 p-4">
                                        <div className="flex items-start gap-3">
                                            <div className="rounded-md bg-blue-500/15 p-2 text-blue-600 dark:text-blue-400">
                                                <Clock className="size-5" />
                                            </div>
                                            <div>
                                                <p className="font-semibold text-blue-700 dark:text-blue-400">
                                                    {
                                                        stats.alerts
                                                            .pendingOrdersCount
                                                    }{' '}
                                                    {stats.alerts
                                                        .pendingOrdersCount ===
                                                    1
                                                        ? 'Pedido Pendiente'
                                                        : 'Pedidos Pendientes'}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Pedidos de venta por
                                                    confirmar o despachar
                                                </p>
                                            </div>
                                        </div>
                                        {companyId && (
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                                className="w-full justify-between border-blue-500/20 text-xs hover:bg-blue-500/10"
                                            >
                                                <Link
                                                    href={
                                                        salesOrders.index(
                                                            companyId,
                                                        ).url
                                                    }
                                                >
                                                    Ver Pedidos
                                                    <ArrowRight className="size-3.5" />
                                                </Link>
                                            </Button>
                                        )}
                                    </CardContent>
                                </Card>
                            ) : null}
                        </div>
                    ) : (
                        <Card className="border-emerald-500/20 bg-emerald-500/5 py-3">
                            <CardContent className="flex items-center gap-3 p-4">
                                <CheckCircle2 className="size-5 text-emerald-600 dark:text-emerald-400" />
                                <div>
                                    <p className="font-semibold text-emerald-700 dark:text-emerald-400">
                                        ¡Todo al día!
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        No hay facturas vencidas ni alertas
                                        críticas en este momento.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* 3. Accesos Rápidos y Últimas Ventas */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Accesos Rápidos */}
                    <Card className="h-fit py-4 lg:col-span-1">
                        <CardHeader>
                            <CardTitle className="text-base font-bold">
                                Accesos Rápidos
                            </CardTitle>
                            <CardDescription className="text-xs">
                                Acciones frecuentes para agilizar tu trabajo
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2.5">
                            {companyId ? (
                                <>
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="h-11 justify-start gap-3 text-sm font-medium hover:bg-accent"
                                    >
                                        <Link
                                            href={
                                                salesInvoices.create(companyId)
                                                    .url
                                            }
                                        >
                                            <div className="flex size-7 items-center justify-center rounded-md bg-primary/10 text-primary">
                                                <FilePlus className="size-4" />
                                            </div>
                                            Nueva Factura de Venta
                                        </Link>
                                    </Button>

                                    <Button
                                        asChild
                                        variant="outline"
                                        className="h-11 justify-start gap-3 text-sm font-medium hover:bg-accent"
                                    >
                                        <Link
                                            href={
                                                clientCollections.create(
                                                    companyId,
                                                ).url
                                            }
                                        >
                                            <div className="flex size-7 items-center justify-center rounded-md bg-blue-500/10 text-blue-600 dark:text-blue-400">
                                                <DollarSign className="size-4" />
                                            </div>
                                            Registrar Cobro
                                        </Link>
                                    </Button>

                                    <Button
                                        asChild
                                        variant="outline"
                                        className="h-11 justify-start gap-3 text-sm font-medium hover:bg-accent"
                                    >
                                        <Link
                                            href={
                                                purchaseInvoices.create(
                                                    companyId,
                                                ).url
                                            }
                                        >
                                            <div className="flex size-7 items-center justify-center rounded-md bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                                <ShoppingCart className="size-4" />
                                            </div>
                                            Registrar Factura de Compra
                                        </Link>
                                    </Button>

                                    <Button
                                        asChild
                                        variant="outline"
                                        className="h-11 justify-start gap-3 text-sm font-medium hover:bg-accent"
                                    >
                                        <Link href={items.index(companyId).url}>
                                            <div className="flex size-7 items-center justify-center rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                                <Package className="size-4" />
                                            </div>
                                            Consultar Inventario
                                        </Link>
                                    </Button>
                                </>
                            ) : (
                                <p className="text-xs text-muted-foreground">
                                    Selecciona una empresa para acceder a las
                                    opciones rápidas.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Últimas Ventas */}
                    <Card className="py-4 lg:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-base font-bold">
                                    Últimas Facturas de Venta
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    Facturas emitidas recientemente en la
                                    empresa
                                </CardDescription>
                            </div>
                            {companyId && (
                                <Button
                                    asChild
                                    variant="ghost"
                                    size="sm"
                                    className="gap-1 text-xs"
                                >
                                    <Link
                                        href={
                                            salesInvoices.index(companyId).url
                                        }
                                    >
                                        Ver todas
                                        <ArrowRight className="size-3.5" />
                                    </Link>
                                </Button>
                            )}
                        </CardHeader>
                        <CardContent>
                            {stats.recentInvoices.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-[100px]">
                                                    Código
                                                </TableHead>
                                                <TableHead>Cliente</TableHead>
                                                <TableHead>Fecha</TableHead>
                                                <TableHead className="text-right">
                                                    Total
                                                </TableHead>
                                                <TableHead className="text-center">
                                                    Estado
                                                </TableHead>
                                                <TableHead className="w-[50px]"></TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {stats.recentInvoices.map((inv) => (
                                                <TableRow key={inv.id}>
                                                    <TableCell className="font-semibold text-primary">
                                                        {inv.code}
                                                    </TableCell>
                                                    <TableCell className="max-w-[180px] truncate font-medium">
                                                        {inv.clientName}
                                                    </TableCell>
                                                    <TableCell className="text-xs whitespace-nowrap text-muted-foreground">
                                                        {inv.invoiceDate}
                                                    </TableCell>
                                                    <TableCell className="text-right font-semibold whitespace-nowrap">
                                                        {inv.currency}{' '}
                                                        {formatAmount(
                                                            inv.total,
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {getPaymentStatusBadge(
                                                            inv.paymentStatus,
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {companyId && (
                                                            <Button
                                                                asChild
                                                                variant="ghost"
                                                                size="icon"
                                                                className="size-7"
                                                                title="Ver detalle"
                                                            >
                                                                <Link
                                                                    href={
                                                                        salesInvoices.show(
                                                                            {
                                                                                company:
                                                                                    companyId,
                                                                                id: inv.id,
                                                                            },
                                                                        ).url
                                                                    }
                                                                >
                                                                    <ExternalLink className="size-3.5" />
                                                                </Link>
                                                            </Button>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            ) : (
                                <div className="flex flex-col items-center justify-center py-10 text-center">
                                    <div className="rounded-full bg-muted p-3 text-muted-foreground">
                                        <Receipt className="size-6" />
                                    </div>
                                    <p className="mt-3 text-sm font-semibold">
                                        No hay facturas registradas
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Las facturas emitidas aparecerán
                                        reflejadas aquí en tiempo real.
                                    </p>
                                    {companyId && (
                                        <Button
                                            asChild
                                            size="sm"
                                            className="mt-4 gap-1.5 text-xs"
                                        >
                                            <Link
                                                href={
                                                    salesInvoices.create(
                                                        companyId,
                                                    ).url
                                                }
                                            >
                                                Crear primera factura
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
