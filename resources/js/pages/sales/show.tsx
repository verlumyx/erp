import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    CalendarClock,
    DollarSign,
    Hash,
    Layers,
    RefreshCw,
    RotateCcw,
    ShoppingCart,
    Timer,
    Tv,
    User,
} from 'lucide-react';
import { useState } from 'react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { clp } from '@/lib/crm-demo';
import sales from '@/routes/sales';
import type { BreadcrumbItem } from '@/types';
import { SaleCancelDialog } from './components/SaleCancelDialog';
import { SaleReactivateDialog } from './components/SaleReactivateDialog';
import { SaleRenewDialog } from './components/SaleRenewDialog';
import {
    SALE_CAPACITY_LABELS,
    SALE_STATUS_LABELS,
    saleStatusPill,
    type AvailableProfile,
    type Sale,
} from './types/Sale';

interface Props {
    sale: Sale;
    availableProfiles: AvailableProfile[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    auth?: { permissions?: string[] };
    [key: string]: unknown;
}

function MiniStat({
    label,
    value,
    icon: Icon,
}: {
    label: string;
    value: string | number;
    icon: typeof Layers;
}) {
    return (
        <Card className="gap-1 rounded-2xl px-[18px] py-4">
            <span className="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-muted-foreground">
                <Icon className="size-3.5" />
                {label}
            </span>
            <span className="text-[21px] font-extrabold tracking-tight tabular-nums">
                {value}
            </span>
        </Card>
    );
}

export default function SalesShow({ sale, availableProfiles }: Props) {
    const { currentCompany, auth } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const can = (p: string) => auth?.permissions?.includes(p) ?? false;

    const [renewOpen, setRenewOpen] = useState<Sale | null>(null);
    const [reactivateOpen, setReactivateOpen] = useState<Sale | null>(null);
    const [cancelOpen, setCancelOpen] = useState<Sale | null>(null);

    const profiles = sale.sale_profiles ?? [];
    const renewals = sale.renewals ?? [];

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Ventas', href: sales.index(companyId).url },
        {
            title: sale.code,
            href: sales.show({ company: companyId, id: sale.id }).url,
        },
    ];

    const daysLabel =
        sale.days_until_expiration >= 0
            ? `${sale.days_until_expiration} días`
            : `${Math.abs(sale.days_until_expiration)} días vencida`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={sale.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={sales.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Ventas
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex items-center gap-[18px]">
                        <span className="grid size-16 shrink-0 place-items-center rounded-[16px] border bg-muted text-muted-foreground">
                            <ShoppingCart className="size-7" />
                        </span>
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-extrabold tracking-tight">
                                    {sale.client?.name ?? '—'}
                                </h1>
                                <StatusPill kind={saleStatusPill(sale.status)}>
                                    {SALE_STATUS_LABELS[sale.status]}
                                </StatusPill>
                                {sale.is_in_grace_period && (
                                    <StatusPill kind="pendiente">
                                        En gracia
                                    </StatusPill>
                                )}
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {sale.code}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Tv className="size-3.5 opacity-80" />
                                    {sale.service?.name ?? '—'}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Layers className="size-3.5 opacity-80" />
                                    {SALE_CAPACITY_LABELS[sale.capacity]}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <User className="size-3.5 opacity-80" />
                                    {sale.agent?.name ?? '—'}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        {sale.can_be_renewed && can('sales.renew') && (
                            <Button
                                className="h-10 rounded-[11px] px-4 font-semibold"
                                onClick={() => setRenewOpen(sale)}
                            >
                                <RefreshCw />
                                Renovar
                            </Button>
                        )}
                        {sale.can_be_reactivated && can('sales.reactivate') && (
                            <Button
                                className="h-10 rounded-[11px] px-4 font-semibold"
                                onClick={() => setReactivateOpen(sale)}
                            >
                                <RotateCcw />
                                Reactivar
                            </Button>
                        )}
                        {sale.status !== 'cancelled' && can('sales.cancel') && (
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                onClick={() => setCancelOpen(sale)}
                            >
                                <Ban />
                                Expulsar
                            </Button>
                        )}
                    </div>
                </Card>

                {sale.can_be_reactivated && sale.status === 'expired' && (
                    <Card className="rounded-2xl border-bad/40 bg-bad-soft p-4 text-sm text-bad">
                        Esta venta venció fuera del periodo de gracia. Al
                        reactivarla se generará un nuevo ciclo; si los profiles
                        originales ya están ocupados deberás elegir otros.
                    </Card>
                )}

                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <MiniStat
                        label="Precio"
                        value={clp(Number(sale.price))}
                        icon={DollarSign}
                    />
                    <MiniStat
                        label="Inicio"
                        value={sale.start_date ?? '—'}
                        icon={CalendarClock}
                    />
                    <MiniStat
                        label="Vencimiento"
                        value={sale.end_date ?? '—'}
                        icon={CalendarClock}
                    />
                    <MiniStat label="Restante" value={daysLabel} icon={Timer} />
                </div>

                {/* Profiles ocupados */}
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b p-5">
                        <div className="inline-flex items-center gap-2 text-base font-bold tracking-tight">
                            <Layers className="size-4 text-muted-foreground" />
                            Perfiles ocupados
                        </div>
                        <span className="text-[12.5px] font-semibold text-muted-foreground">
                            {profiles.length} perfil
                            {profiles.length !== 1 ? 'es' : ''}
                        </span>
                    </div>
                    <div className="hidden h-11 items-center gap-3 border-b bg-muted px-5 text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase lg:grid lg:grid-cols-[80px_2fr_1fr]">
                        <div>Perfil</div>
                        <div>Cuenta</div>
                        <div>Estado</div>
                    </div>
                    <div className="flex flex-col">
                        {profiles.map((row) => (
                            <div
                                key={row.id}
                                className="grid grid-cols-1 items-center gap-3 border-b px-5 py-3 last:border-b-0 lg:grid-cols-[80px_2fr_1fr]"
                            >
                                <div className="font-bold text-muted-foreground tabular-nums">
                                    #{row.number ?? '—'}
                                </div>
                                <div className="truncate font-semibold">
                                    {row.account?.email ?? '—'}
                                </div>
                                <div className="text-[13.5px] text-muted-foreground">
                                    {row.profile_status ?? '—'}
                                </div>
                            </div>
                        ))}
                        {profiles.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                Esta venta no tiene perfiles asignados.
                            </div>
                        )}
                    </div>
                </Card>

                {/* Renovaciones */}
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="flex items-center gap-2 border-b p-5 text-base font-bold tracking-tight">
                        <RefreshCw className="size-4 text-muted-foreground" />
                        Historial de renovaciones
                    </div>
                    <div className="flex flex-col">
                        {renewals.map((r) => (
                            <div
                                key={r.id}
                                className="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-3 last:border-b-0"
                            >
                                <div className="flex flex-col">
                                    <span className="font-semibold">
                                        {r.renewed_at}
                                    </span>
                                    <span className="text-[12.5px] text-muted-foreground">
                                        {r.previous_end_date} → {r.new_end_date}{' '}
                                        ({r.duration_days} días)
                                    </span>
                                </div>
                                <span className="font-bold tabular-nums">
                                    {clp(Number(r.price))}
                                </span>
                            </div>
                        ))}
                        {renewals.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                Sin renovaciones registradas.
                            </div>
                        )}
                    </div>
                </Card>

                {sale.notes && (
                    <Card className="gap-2 rounded-2xl p-5">
                        <div className="text-base font-bold tracking-tight">
                            Notas
                        </div>
                        <p className="text-[13.5px] whitespace-pre-line text-muted-foreground">
                            {sale.notes}
                        </p>
                    </Card>
                )}

                {sale.status === 'cancelled' && sale.cancellation_reason && (
                    <Card className="gap-2 rounded-2xl p-5">
                        <div className="text-base font-bold tracking-tight">
                            Motivo de expulsión
                        </div>
                        <p className="text-[13.5px] text-muted-foreground">
                            {sale.cancellation_reason}
                        </p>
                    </Card>
                )}
            </div>

            <SaleRenewDialog
                companyId={companyId}
                sale={renewOpen}
                onClose={() => setRenewOpen(null)}
            />
            <SaleReactivateDialog
                companyId={companyId}
                sale={reactivateOpen}
                availableProfiles={availableProfiles}
                onClose={() => setReactivateOpen(null)}
            />
            <SaleCancelDialog
                companyId={companyId}
                sale={cancelOpen}
                onClose={() => setCancelOpen(null)}
            />
        </AppLayout>
    );
}
