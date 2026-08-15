import { Deferred, Head, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CreditCard,
    Download,
    Plus,
    TrendingUp,
    Wallet,
} from 'lucide-react';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/app-layout';
import { money, percent } from '@/lib/format';
import clients from '@/routes/clients';
import company from '@/routes/company';
import { type BreadcrumbItem } from '@/types';
import { DashboardOccupancy } from './dashboard/components/DashboardOccupancy';
import { DashboardPlataformas } from './dashboard/components/DashboardPlataformas';
import { DashboardRevenueChart } from './dashboard/components/DashboardRevenueChart';
import { DashboardVencimientos } from './dashboard/components/DashboardVencimientos';
import type {
    DashboardExpiration,
    DashboardMetrics,
    DashboardOccupancy as Occupancy,
    DashboardPlatform,
    DashboardRevenuePoint,
} from './dashboard/types';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    metrics?: DashboardMetrics;
    revenue?: DashboardRevenuePoint[];
    occupancy?: Occupancy;
    platforms?: DashboardPlatform[];
    expirations?: DashboardExpiration[];
    [key: string]: unknown;
}

const MESES_LARGOS = [
    'enero',
    'febrero',
    'marzo',
    'abril',
    'mayo',
    'junio',
    'julio',
    'agosto',
    'septiembre',
    'octubre',
    'noviembre',
    'diciembre',
];

function trendOf(pct: number | null) {
    if (pct === null) {
        return undefined;
    }
    return {
        dir: pct >= 0 ? ('up' as const) : ('down' as const),
        value: percent(pct),
    };
}

function DashboardStats({ metrics }: { metrics: DashboardMetrics }) {
    return (
        <>
            <StatCard
                icon={Wallet}
                tone="primary"
                label="Ingresos del mes"
                value={money(metrics.income_month)}
                trend={trendOf(metrics.income_trend_pct)}
                sub="vs. mes anterior"
            />
            <StatCard
                icon={TrendingUp}
                tone="ok"
                label="Ganancia neta"
                value={money(metrics.net_profit)}
                trend={trendOf(metrics.profit_trend_pct)}
                sub={`margen ${metrics.profit_margin_pct}%`}
            />
            <StatCard
                icon={CreditCard}
                tone="info"
                label="Perfiles activos"
                value={metrics.active_profiles}
                sub={`${metrics.free_profiles} libres por vender`}
            />
            <StatCard
                icon={AlertTriangle}
                tone="warn"
                label="Por cobrar"
                value={money(metrics.receivable_amount)}
                sub={`${metrics.receivable_clients} clientes con deuda`}
            />
        </>
    );
}

function CardSkeleton({ className = '' }: { className?: string }) {
    return (
        <Card className={`rounded-2xl p-5 ${className}`}>
            <Skeleton className="h-full w-full" />
        </Card>
    );
}

function StatsSkeleton() {
    return (
        <>
            {Array.from({ length: 4 }).map((_, i) => (
                <CardSkeleton key={i} className="h-[132px]" />
            ))}
        </>
    );
}

export default function Dashboard() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Resumen',
            href: company.dashboard(companyId).url,
        },
    ];

    const hoy = new Date();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Resumen" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                    <div>
                        <h1 className="text-[27px] font-extrabold tracking-tight">
                            Resumen
                        </h1>
                        <p className="mt-1 text-[14.5px] text-muted-foreground">
                            Tu negocio de streaming de un vistazo ·{' '}
                            {MESES_LARGOS[hoy.getMonth()]} {hoy.getFullYear()}
                        </p>
                    </div>
                    <div className="flex gap-2.5">
                        <Button
                            variant="outline"
                            className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                        >
                            <Download />
                            Exportar
                        </Button>
                        <Button
                            className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                            onClick={() =>
                                router.visit(clients.create(companyId).url)
                            }
                        >
                            <Plus />
                            Nueva venta
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <Deferred data="metrics" fallback={<StatsSkeleton />}>
                        <DashboardStatsDeferred />
                    </Deferred>
                </div>

                <div className="grid grid-cols-1 gap-5 xl:grid-cols-[1.7fr_1fr]">
                    <Deferred
                        data="revenue"
                        fallback={<CardSkeleton className="h-[300px]" />}
                    >
                        <DashboardRevenueDeferred />
                    </Deferred>
                    <Deferred
                        data="occupancy"
                        fallback={<CardSkeleton className="h-[260px]" />}
                    >
                        <DashboardOccupancyDeferred />
                    </Deferred>
                </div>

                <div className="grid grid-cols-1 gap-5 xl:grid-cols-[1.25fr_1fr]">
                    <Deferred
                        data="expirations"
                        fallback={<CardSkeleton className="h-[300px]" />}
                    >
                        <DashboardVencimientosDeferred companyId={companyId} />
                    </Deferred>
                    <Deferred
                        data="platforms"
                        fallback={<CardSkeleton className="h-[300px]" />}
                    >
                        <DashboardPlataformasDeferred />
                    </Deferred>
                </div>
            </div>
        </AppLayout>
    );
}

function DashboardStatsDeferred() {
    const { metrics } = usePage<PageProps>().props;
    return <DashboardStats metrics={metrics!} />;
}

function DashboardRevenueDeferred() {
    const { revenue } = usePage<PageProps>().props;
    return <DashboardRevenueChart data={revenue!} />;
}

function DashboardOccupancyDeferred() {
    const { occupancy } = usePage<PageProps>().props;
    return <DashboardOccupancy occupancy={occupancy!} />;
}

function DashboardVencimientosDeferred({ companyId }: { companyId: string }) {
    const { expirations } = usePage<PageProps>().props;
    return (
        <DashboardVencimientos
            companyId={companyId}
            expirations={expirations!}
        />
    );
}

function DashboardPlataformasDeferred() {
    const { platforms } = usePage<PageProps>().props;
    return <DashboardPlataformas platforms={platforms!} />;
}
