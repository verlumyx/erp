import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarClock,
    DollarSign,
    Edit,
    Hash,
    Package,
    Power,
    Tv,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { clp } from '@/lib/crm-demo';
import AppLayout from '@/layouts/app-layout';
import plans from '@/routes/plans';
import type { BreadcrumbItem } from '@/types';
import { CAPACITY_LABELS, type Plan } from './types/Plan';

interface Props {
    plan: Plan;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function MiniStat({
    label,
    value,
    icon: Icon,
}: {
    label: string;
    value: string | number;
    icon: typeof Package;
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

export default function PlansShow({ plan }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({ active: !plan.active });

    const handleToggleStatus = () => {
        put(plans.updateStatus({ company: companyId, id: plan.id }).url);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Planes', href: plans.index(companyId).url },
        {
            title: plan.name,
            href: plans.show({ company: companyId, id: plan.id }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={plan.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={plans.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Planes
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex items-center gap-[18px]">
                        <span className="grid size-16 shrink-0 place-items-center rounded-[16px] border bg-muted text-muted-foreground">
                            <Package className="size-7" />
                        </span>
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-extrabold tracking-tight">
                                    {plan.name}
                                </h1>
                                <StatusPill
                                    kind={plan.active ? 'activo' : 'inactivo'}
                                />
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {plan.code}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Tv className="size-3.5 opacity-80" />
                                    {plan.service?.name ?? '—'}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Package className="size-3.5 opacity-80" />
                                    {CAPACITY_LABELS[plan.capacity]}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        <Button
                            variant="outline"
                            className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            onClick={handleToggleStatus}
                            disabled={processing}
                        >
                            <Power />
                            {plan.active ? 'Desactivar' : 'Activar'}
                        </Button>
                        <Link
                            href={
                                plans.edit({ company: companyId, id: plan.id })
                                    .url
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
                    </div>
                </Card>

                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <MiniStat
                        label="Precio de venta"
                        value={clp(Number(plan.sale_price))}
                        icon={DollarSign}
                    />
                    <MiniStat
                        label="Duración"
                        value={`${plan.duration_days} días`}
                        icon={CalendarClock}
                    />
                    <MiniStat
                        label="Meta ROI"
                        value={`${plan.roi_target_pct}%`}
                        icon={DollarSign}
                    />
                    <MiniStat
                        label="Capacidad"
                        value={CAPACITY_LABELS[plan.capacity]}
                        icon={Package}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
