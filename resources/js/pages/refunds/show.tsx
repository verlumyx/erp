import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    DollarSign,
    Hash,
    Pencil,
    ShoppingCart,
    Undo2,
    User,
    X,
} from 'lucide-react';
import { useState } from 'react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { clp } from '@/lib/crm-demo';
import refunds from '@/routes/refunds';
import type { BreadcrumbItem } from '@/types';
import { RefundForm } from './components/RefundForm';
import { RefundFormProvider } from './contexts/RefundFormContext';
import { useRefundForm } from './hooks/useRefundForm';
import {
    REFUND_STATUS_LABELS,
    refundStatusPill,
    type Refund,
} from './types/Refund';

interface Props {
    refund: Refund;
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
    value: string;
    icon: typeof Hash;
}) {
    return (
        <Card className="gap-1 rounded-2xl px-[18px] py-4">
            <span className="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-muted-foreground">
                <Icon className="size-3.5" />
                {label}
            </span>
            <span className="text-[19px] font-extrabold tracking-tight tabular-nums">
                {value}
            </span>
        </Card>
    );
}

export default function RefundsShow({ refund }: Props) {
    const { currentCompany, auth } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const can = (p: string) => auth?.permissions?.includes(p) ?? false;

    const [editing, setEditing] = useState(false);
    const formState = useRefundForm({ mode: 'edit', refund });

    const transactions = refund.transactions ?? [];

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Reembolsos', href: refunds.index(companyId).url },
        {
            title: refund.code,
            href: refunds.show({ company: companyId, id: refund.id }).url,
        },
    ];

    const resolve = (action: 'approve' | 'reject') => {
        const url =
            action === 'approve'
                ? refunds.approve({ company: companyId, id: refund.id }).url
                : refunds.reject({ company: companyId, id: refund.id }).url;
        router.post(url, {}, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={refund.code} />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={refunds.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Reembolsos
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex items-center gap-[18px]">
                        <span className="grid size-16 shrink-0 place-items-center rounded-[16px] border bg-muted text-muted-foreground">
                            <Undo2 className="size-7" />
                        </span>
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-extrabold tracking-tight">
                                    {refund.client?.name ?? '—'}
                                </h1>
                                <StatusPill
                                    kind={refundStatusPill(refund.status)}
                                >
                                    {REFUND_STATUS_LABELS[refund.status]}
                                </StatusPill>
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {refund.code}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <ShoppingCart className="size-3.5 opacity-80" />
                                    Venta {refund.sale?.code ?? '—'}
                                </span>
                                {refund.requested_by_user?.name && (
                                    <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                        <User className="size-3.5 opacity-80" />
                                        {refund.requested_by_user.name}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                    {refund.is_pending && (
                        <div className="flex flex-wrap gap-2.5">
                            {can('refunds.update') && !editing && (
                                <Button
                                    variant="outline"
                                    className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                    onClick={() => setEditing(true)}
                                >
                                    <Pencil />
                                    Editar
                                </Button>
                            )}
                            {can('refunds.approve') && (
                                <Button
                                    className="h-10 rounded-[11px] px-4 font-semibold"
                                    onClick={() => resolve('approve')}
                                >
                                    <Check />
                                    Aprobar
                                </Button>
                            )}
                            {can('refunds.reject') && (
                                <Button
                                    variant="destructive"
                                    className="h-10 rounded-[11px] px-4 font-semibold"
                                    onClick={() => resolve('reject')}
                                >
                                    <X />
                                    Rechazar
                                </Button>
                            )}
                        </div>
                    )}
                </Card>

                {editing ? (
                    <Card className="rounded-2xl p-6">
                        <h2 className="mb-4 text-base font-bold tracking-tight">
                            Editar reembolso
                        </h2>
                        <RefundFormProvider value={formState}>
                            <RefundForm
                                refund={refund}
                                onCancel={() => setEditing(false)}
                            />
                        </RefundFormProvider>
                    </Card>
                ) : (
                    <>
                        <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <MiniStat
                                label="Monto"
                                value={clp(Number(refund.amount))}
                                icon={DollarSign}
                            />
                            <MiniStat
                                label="Venta"
                                value={refund.sale?.code ?? '—'}
                                icon={ShoppingCart}
                            />
                            <MiniStat
                                label="Resuelto"
                                value={refund.resolved_at ?? '—'}
                                icon={Check}
                            />
                        </div>

                        {refund.reason && (
                            <Card className="gap-2 rounded-2xl p-5">
                                <div className="text-base font-bold tracking-tight">
                                    Razón
                                </div>
                                <p className="text-[13.5px] whitespace-pre-line text-muted-foreground">
                                    {refund.reason}
                                </p>
                            </Card>
                        )}

                        <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                            <div className="flex items-center gap-2 border-b p-5 text-base font-bold tracking-tight">
                                <DollarSign className="size-4 text-muted-foreground" />
                                Transacciones
                            </div>
                            <div className="flex flex-col">
                                {transactions.map((t) => (
                                    <div
                                        key={t.id}
                                        className="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-3 last:border-b-0"
                                    >
                                        <div className="flex flex-col">
                                            <span className="font-semibold">
                                                {t.description}
                                            </span>
                                            <span className="text-[12.5px] text-muted-foreground">
                                                {t.date} · {t.category}
                                            </span>
                                        </div>
                                        <span className="font-bold text-bad tabular-nums">
                                            -{clp(Number(t.amount))}
                                        </span>
                                    </div>
                                ))}
                                {transactions.length === 0 && (
                                    <div className="p-8 text-center text-sm text-muted-foreground">
                                        Sin transacciones asociadas. El egreso
                                        se registra al aprobar el reembolso.
                                    </div>
                                )}
                            </div>
                        </Card>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
