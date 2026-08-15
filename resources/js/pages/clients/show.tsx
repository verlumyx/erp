import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Clock,
    Edit,
    Mail,
    Phone,
    Plus,
    Power,
    StickyNote,
    Tv,
} from 'lucide-react';
import { InitialsAvatar } from '@/components/initials-avatar';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { WhatsAppAction, WhatsAppButton } from '@/components/whatsapp-button';
import AppLayout from '@/layouts/app-layout';
import { clp, fmtFecha, mesesDesde } from '@/lib/crm-demo';
import clients from '@/routes/clients';
import sales from '@/routes/sales';
import type { BreadcrumbItem } from '@/types';
import {
    SALE_CAPACITY_LABELS,
    SALE_STATUS_LABELS,
    saleStatusPill,
    type Sale,
} from '../sales/types/Sale';
import type { Client } from './types/Client';

interface ClientMetrics {
    monthly_income: number;
    pending_debt: number;
    total_paid: number;
}

interface Props {
    client: Client;
    sales: Sale[];
    metrics: ClientMetrics;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    auth?: { permissions?: string[] };
    [key: string]: unknown;
}

function MiniStat({
    label,
    value,
    accent = false,
}: {
    label: string;
    value: string | number;
    accent?: boolean;
}) {
    return (
        <Card className="gap-1 rounded-2xl px-[18px] py-4">
            <span className="text-[12.5px] font-semibold text-muted-foreground">
                {label}
            </span>
            <span
                className={`text-[21px] font-extrabold tracking-tight tabular-nums ${accent ? 'text-bad' : ''}`}
            >
                {value}
            </span>
        </Card>
    );
}

export default function ClientsShow({
    client,
    sales: clientSales,
    metrics,
}: Props) {
    const { currentCompany, auth } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const canSell =
        (auth?.permissions?.includes('sales.create') ?? false) &&
        client.status === 'active';
    const activeSalesCount = clientSales.filter(
        (s) => s.status === 'active',
    ).length;

    const { put, processing } = useForm({
        status: client.status === 'active' ? 'inactive' : 'active',
    });

    const tel = client.phone ?? '';
    const estadoCliente =
        client.status === 'inactive' ? 'inactivo' : 'activo';
    const antig = mesesDesde(client.created_at);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Clientes', href: clients.index(companyId).url },
        {
            title: client.name,
            href: clients.show({ company: companyId, id: client.id }).url,
        },
    ];

    const handleToggleStatus = () => {
        put(clients.updateStatus({ company: companyId, id: client.id }).url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={client.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={clients.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Clientes
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex items-center gap-[18px]">
                        <InitialsAvatar name={client.name} size={64} />
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-extrabold tracking-tight">
                                    {client.name}
                                </h1>
                                <StatusPill kind={estadoCliente} />
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                                {tel && (
                                    <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                        <Phone className="size-3.5 opacity-80" />
                                        {tel}
                                    </span>
                                )}
                                {client.email && (
                                    <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                        <Mail className="size-3.5 opacity-80" />
                                        {client.email}
                                    </span>
                                )}
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Clock className="size-3.5 opacity-80" />
                                    Cliente hace {antig} mes
                                    {antig !== 1 ? 'es' : ''}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        {tel && <WhatsAppAction tel={tel} />}
                        <Button
                            variant="outline"
                            className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            onClick={handleToggleStatus}
                            disabled={processing}
                        >
                            <Power />
                            {client.status === 'active'
                                ? 'Desactivar'
                                : 'Activar'}
                        </Button>
                        <Link
                            href={
                                clients.edit({
                                    company: companyId,
                                    id: client.id,
                                }).url
                            }
                        >
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            >
                                <Edit />
                                Editar
                            </Button>
                        </Link>
                        {canSell ? (
                            <Link
                                href={
                                    sales.create(companyId, {
                                        query: { client: client.id },
                                    }).url
                                }
                            >
                                <Button className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]">
                                    <Plus />
                                    Vender perfil
                                </Button>
                            </Link>
                        ) : (
                            <Button
                                className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                                disabled
                                title={
                                    client.status === 'active'
                                        ? 'No tienes permiso para crear ventas'
                                        : 'El cliente debe estar activo para venderle'
                                }
                            >
                                <Plus />
                                Vender perfil
                            </Button>
                        )}
                    </div>
                </Card>

                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <MiniStat
                        label="Ingreso mensual"
                        value={clp(metrics.monthly_income)}
                    />
                    <MiniStat label="Perfiles activos" value={activeSalesCount} />
                    <MiniStat
                        label="Deuda pendiente"
                        value={clp(metrics.pending_debt)}
                        accent={metrics.pending_debt > 0}
                    />
                    <MiniStat
                        label="Total pagado (histórico)"
                        value={clp(metrics.total_paid)}
                    />
                </div>

                <div className="grid grid-cols-1 items-start gap-5">
                    <div className="flex min-w-0 flex-col gap-5">
                        <div className="flex items-center gap-2 text-[15px] font-bold">
                            Perfiles activos
                            <span className="rounded-full border bg-muted px-2 py-px text-xs font-bold text-muted-foreground">
                                {clientSales.length}
                            </span>
                        </div>
                        {clientSales.length ? (
                            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                                {clientSales.map((sale) => {
                                    const saleProfiles =
                                        sale.sale_profiles ?? [];
                                    const cuenta =
                                        saleProfiles[0]?.account?.email ?? '—';
                                    const perfil =
                                        sale.capacity === 'full_account'
                                            ? SALE_CAPACITY_LABELS.full_account
                                            : saleProfiles.length
                                              ? saleProfiles
                                                    .map(
                                                        (sp) =>
                                                            `#${sp.number ?? '—'}`,
                                                    )
                                                    .join(', ')
                                              : '—';
                                    return (
                                        <Card
                                            key={sale.id}
                                            className="gap-3.5 rounded-2xl border-t-[3px] border-t-primary/60 p-4"
                                        >
                                            <div className="flex items-center justify-between">
                                                <span className="inline-flex items-center gap-2 text-[13.5px] font-bold">
                                                    <Tv className="size-4 text-muted-foreground" />
                                                    {sale.service?.name ?? '—'}
                                                </span>
                                                <StatusPill
                                                    kind={saleStatusPill(
                                                        sale.status,
                                                    )}
                                                >
                                                    {
                                                        SALE_STATUS_LABELS[
                                                            sale.status
                                                        ]
                                                    }
                                                </StatusPill>
                                            </div>
                                            <div className="flex flex-col gap-2">
                                                <div className="flex items-center justify-between gap-2.5 text-[13px]">
                                                    <span className="font-medium text-muted-foreground">
                                                        Perfil
                                                    </span>
                                                    <b className="font-bold whitespace-nowrap">
                                                        {perfil}
                                                    </b>
                                                </div>
                                                <div className="flex items-center justify-between gap-2.5 text-[13px]">
                                                    <span className="font-medium text-muted-foreground">
                                                        Cuenta
                                                    </span>
                                                    <b className="max-w-[165px] truncate font-mono text-[11.5px] font-bold">
                                                        {cuenta}
                                                    </b>
                                                </div>
                                                <div className="flex items-center justify-between gap-2.5 text-[13px]">
                                                    <span className="font-medium text-muted-foreground">
                                                        Precio
                                                    </span>
                                                    <b className="font-bold whitespace-nowrap">
                                                        {clp(Number(sale.price))}
                                                    </b>
                                                </div>
                                                <div className="flex items-center justify-between gap-2.5 text-[13px]">
                                                    <span className="font-medium text-muted-foreground">
                                                        Vence
                                                    </span>
                                                    <b className="font-bold whitespace-nowrap">
                                                        {sale.end_date
                                                            ? fmtFecha(
                                                                  new Date(
                                                                      `${sale.end_date}T00:00:00`,
                                                                  ),
                                                              )
                                                            : '—'}
                                                    </b>
                                                </div>
                                            </div>
                                            <div className="mt-0.5 flex gap-2">
                                                <Link
                                                    href={
                                                        sales.show({
                                                            company: companyId,
                                                            id: sale.id,
                                                        }).url
                                                    }
                                                    className="flex-1"
                                                >
                                                    <Button
                                                        variant="secondary"
                                                        size="sm"
                                                        className="h-[33px] w-full rounded-[9px] font-semibold"
                                                    >
                                                        Ver venta
                                                    </Button>
                                                </Link>
                                                {tel && (
                                                    <WhatsAppButton
                                                        tel={tel}
                                                        label="Avisar"
                                                        size="sm"
                                                    />
                                                )}
                                            </div>
                                        </Card>
                                    );
                                })}
                            </div>
                        ) : (
                            <Card className="rounded-2xl p-8 text-center text-sm text-muted-foreground">
                                Este cliente no tiene perfiles activos.
                            </Card>
                        )}

                        {client.notes && (
                            <Card className="gap-2 rounded-2xl px-[18px] py-4">
                                <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                                    <StickyNote className="size-[15px]" />
                                    Nota
                                </div>
                                <p className="text-sm leading-relaxed whitespace-pre-wrap">
                                    {client.notes}
                                </p>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
