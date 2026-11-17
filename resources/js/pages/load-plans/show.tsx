import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    Calendar,
    CheckCircle2,
    Edit,
    FileText,
    MapPin,
    Package,
    Truck,
    User,
    Warehouse,
    XCircle,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import loadPlans from '@/routes/load-plans';
import type { BreadcrumbItem } from '@/types';
import {
    STATUS_LABELS,
    STATUS_PILL_KIND,
    type LoadPlan,
    type LoadPlanStatus,
} from './types/LoadPlan';

interface Props {
    loadPlan: LoadPlan;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function DataRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-4 text-[13.5px]">
            <span className="font-medium text-muted-foreground">{label}</span>
            <b className="text-right font-semibold">{value}</b>
        </div>
    );
}

export default function LoadPlansShow({ loadPlan: plan }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Planes de Carga', href: loadPlans.index(companyId).url },
        {
            title: plan.code,
            href: loadPlans.show({ company: companyId, id: plan.id }).url,
        },
    ];

    const { put, processing, transform } = useForm<{ status: LoadPlanStatus }>({
        status: plan.status,
    });

    const handleSwitchStatus = (status: LoadPlanStatus) => {
        transform(() => ({ status }));
        put(
            loadPlans.updateStatus({ company: companyId, id: plan.id }).url,
            { preserveScroll: true },
        );
    };

    const dispatches = plan.dispatches ?? [];

    const weightPercent = plan.vehicle_capacity_weight > 0
        ? Math.round((plan.total_weight / plan.vehicle_capacity_weight) * 100)
        : 0;

    const volumePercent = plan.vehicle_capacity_volume > 0
        ? Math.round((plan.total_volume / plan.vehicle_capacity_volume) * 100)
        : 0;

    const isWeightOverload =
        plan.vehicle_capacity_weight > 0 &&
        plan.total_weight > plan.vehicle_capacity_weight;
    const isVolumeOverload =
        plan.vehicle_capacity_volume > 0 &&
        plan.total_volume > plan.vehicle_capacity_volume;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Plan de Carga ${plan.code}`} />

            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6 pb-14">
                <Link
                    href={loadPlans.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Planes de Carga
                </Link>

                {/* Header Banner */}
                <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center gap-4">
                        <div className="grid size-14 place-items-center rounded-2xl bg-primary/10 text-primary">
                            <Truck className="size-7" />
                        </div>
                        <div>
                            <div className="flex items-center gap-2.5">
                                <h1 className="text-2xl font-extrabold tracking-tight">
                                    {plan.code}
                                </h1>
                                <StatusPill
                                    label={
                                        STATUS_LABELS[plan.status] ??
                                        plan.status
                                    }
                                    kind={
                                        STATUS_PILL_KIND[plan.status] ??
                                        'neutral'
                                    }
                                />
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground flex items-center gap-2">
                                <span className="flex items-center gap-1">
                                    <Calendar className="size-3.5" />
                                    Fecha: {plan.plan_date}
                                </span>
                                <span>•</span>
                                <span className="flex items-center gap-1">
                                    <MapPin className="size-3.5" />
                                    Ruta: {plan.route_name || '—'}
                                </span>
                            </p>
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex flex-wrap items-center gap-2">
                        {plan.is_editable && (
                            <Link
                                href={
                                    loadPlans.edit({
                                        company: companyId,
                                        id: plan.id,
                                    }).url
                                }
                            >
                                <Button
                                    variant="outline"
                                    className="gap-1.5 h-9 text-xs font-semibold"
                                >
                                    <Edit className="size-3.5" />
                                    Editar plan
                                </Button>
                            </Link>
                        )}

                        {plan.status === 'draft' && (
                            <Button
                                onClick={() => handleSwitchStatus('in_transit')}
                                disabled={processing}
                                className="gap-1.5 h-9 text-xs font-semibold bg-amber-600 hover:bg-amber-700 text-white"
                            >
                                <Truck className="size-3.5" />
                                Despachar a tránsito
                            </Button>
                        )}

                        {plan.status === 'in_transit' && (
                            <>
                                <Button
                                    onClick={() => handleSwitchStatus('confirmed')}
                                    disabled={processing}
                                    className="gap-1.5 h-9 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white"
                                >
                                    <CheckCircle2 className="size-3.5" />
                                    Confirmar entrega
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => handleSwitchStatus('draft')}
                                    disabled={processing}
                                    className="gap-1.5 h-9 text-xs"
                                >
                                    Regresar a borrador
                                </Button>
                            </>
                        )}

                        {(plan.status === 'draft' || plan.status === 'in_transit') && (
                            <Button
                                variant="outline"
                                onClick={() => handleSwitchStatus('cancelled')}
                                disabled={processing}
                                className="gap-1.5 h-9 text-xs text-bad hover:bg-bad/10 hover:text-bad"
                            >
                                <XCircle className="size-3.5" />
                                Anular
                            </Button>
                        )}
                    </div>
                </div>

                {/* Overload Alert if any */}
                {(isWeightOverload || isVolumeOverload) && (
                    <div className="flex items-center gap-3 p-4 rounded-xl bg-bad/10 border border-bad/30 text-bad text-xs font-semibold">
                        <AlertTriangle className="size-5 shrink-0" />
                        <div>
                            {isWeightOverload && (
                                <p>
                                    ¡Atención! El peso total consolidado (
                                    {Number(plan.total_weight).toLocaleString('es-VE')} kg)
                                    supera la capacidad autorizada del camión (
                                    {Number(plan.vehicle_capacity_weight).toLocaleString('es-VE')} kg).
                                </p>
                            )}
                            {isVolumeOverload && (
                                <p>
                                    ¡Atención! El volumen cúbico consolidado (
                                    {Number(plan.total_volume).toLocaleString('es-VE')} m³)
                                    supera el volumen de carga del camión (
                                    {Number(plan.vehicle_capacity_volume).toLocaleString('es-VE')} m³).
                                </p>
                            )}
                        </div>
                    </div>
                )}

                {/* Info Cards Grid */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {/* Vehicle & Capacities */}
                    <Card className="rounded-2xl p-5 flex flex-col gap-4">
                        <div className="flex items-center gap-2 font-bold text-sm">
                            <Truck className="size-4 text-primary" />
                            Camión y Ocupación
                        </div>
                        <div className="flex flex-col gap-2.5">
                            <DataRow
                                label="Placa"
                                value={
                                    plan.vehicle_plate ? (
                                        <span className="font-mono bg-muted px-2 py-0.5 rounded">
                                            {plan.vehicle_plate}
                                        </span>
                                    ) : (
                                        '—'
                                    )
                                }
                            />
                            <DataRow
                                label="Descripción"
                                value={plan.vehicle_name || '—'}
                            />
                            <div className="pt-2 border-t flex flex-col gap-1.5">
                                <div className="flex justify-between text-xs">
                                    <span className="text-muted-foreground font-medium">
                                        Peso ({weightPercent}%)
                                    </span>
                                    <span className="font-bold tabular-nums">
                                        {Number(plan.total_weight).toLocaleString('es-VE', { maximumFractionDigits: 1 })}{' '}
                                        /{' '}
                                        {Number(plan.vehicle_capacity_weight).toLocaleString('es-VE')}{' '}
                                        kg
                                    </span>
                                </div>
                                <div className="h-2 w-full bg-muted rounded-full overflow-hidden">
                                    <div
                                        className={`h-full rounded-full ${
                                            isWeightOverload
                                                ? 'bg-bad'
                                                : weightPercent > 85
                                                  ? 'bg-warning'
                                                  : 'bg-primary'
                                        }`}
                                        style={{ width: `${Math.min(weightPercent, 100)}%` }}
                                    />
                                </div>
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <div className="flex justify-between text-xs">
                                    <span className="text-muted-foreground font-medium">
                                        Volumen ({volumePercent}%)
                                    </span>
                                    <span className="font-bold tabular-nums">
                                        {Number(plan.total_volume).toLocaleString('es-VE', { maximumFractionDigits: 2 })}{' '}
                                        /{' '}
                                        {Number(plan.vehicle_capacity_volume).toLocaleString('es-VE')}{' '}
                                        m³
                                    </span>
                                </div>
                                <div className="h-2 w-full bg-muted rounded-full overflow-hidden">
                                    <div
                                        className={`h-full rounded-full ${
                                            isVolumeOverload
                                                ? 'bg-bad'
                                                : volumePercent > 85
                                                  ? 'bg-warning'
                                                  : 'bg-emerald-500'
                                        }`}
                                        style={{ width: `${Math.min(volumePercent, 100)}%` }}
                                    />
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* Crew & Origin */}
                    <Card className="rounded-2xl p-5 flex flex-col gap-4">
                        <div className="flex items-center gap-2 font-bold text-sm">
                            <User className="size-4 text-primary" />
                            Personal y Despacho
                        </div>
                        <div className="flex flex-col gap-2.5">
                            <DataRow
                                label="Conductor"
                                value={plan.driver_name}
                            />
                            <DataRow
                                label="Ayudante"
                                value={plan.helper_name || 'Sin ayudante'}
                            />
                            <DataRow
                                label="Bodega de Salida"
                                value={plan.warehouse_name || '—'}
                            />
                            <DataRow
                                label="Total Despachos"
                                value={`${plan.total_dispatches} entrega(s)`}
                            />
                        </div>
                    </Card>

                    {/* Operational Details */}
                    <Card className="rounded-2xl p-5 flex flex-col gap-4">
                        <div className="flex items-center gap-2 font-bold text-sm">
                            <Warehouse className="size-4 text-primary" />
                            Detalles Administrativos
                        </div>
                        <div className="flex flex-col gap-2.5">
                            <DataRow
                                label="Ruta / Flete"
                                value={plan.route_name || '—'}
                            />
                            <DataRow
                                label="Creado por"
                                value={plan.creator_name || '—'}
                            />
                            <DataRow
                                label="Fecha Registro"
                                value={plan.created_at || '—'}
                            />
                            {plan.description && (
                                <div className="pt-2 border-t text-xs">
                                    <span className="text-muted-foreground font-medium block mb-1">
                                        Observaciones:
                                    </span>
                                    <p className="text-foreground italic bg-muted/30 p-2 rounded">
                                        {plan.description}
                                    </p>
                                </div>
                            )}
                        </div>
                    </Card>
                </div>

                {/* Dispatches Table Card */}
                <Card className="rounded-2xl p-5 flex flex-col gap-4 border shadow-sm">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="font-bold text-base flex items-center gap-2">
                                <Package className="size-5 text-primary" />
                                Despachos Incluidos en la Carga ({dispatches.length})
                            </h3>
                            <p className="text-xs text-muted-foreground">
                                Recorrido de entrega consolidado para el viaje
                            </p>
                        </div>
                    </div>

                    <div className="border rounded-[10px] overflow-hidden mt-1">
                        <table className="w-full text-xs text-left">
                            <thead className="bg-muted/50 border-b font-semibold text-muted-foreground uppercase text-[11px]">
                                <tr>
                                    <th className="p-3 w-12 text-center">Sec.</th>
                                    <th className="p-3">Despacho</th>
                                    <th className="p-3">Cliente / Destinatario</th>
                                    <th className="p-3">Factura / Pedido</th>
                                    <th className="p-3 text-right">Peso (kg)</th>
                                    <th className="p-3 text-right">Volumen (m³)</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {dispatches.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="p-8 text-center text-muted-foreground"
                                        >
                                            No hay despachos asociados a este plan de carga.
                                        </td>
                                    </tr>
                                ) : (
                                    dispatches.map((d, index) => (
                                        <tr
                                            key={d.id ?? d.dispatch_id}
                                            className="hover:bg-muted/20 transition-colors"
                                        >
                                            <td className="p-3 text-center">
                                                <span className="grid size-6 place-items-center rounded-md bg-primary/10 text-xs font-bold text-primary mx-auto">
                                                    {d.sequence || index + 1}
                                                </span>
                                            </td>
                                            <td className="p-3 font-semibold text-primary">
                                                <div>{d.dispatch_code || d.dispatch_id.slice(0, 8)}</div>
                                                {d.dispatch_date && (
                                                    <div className="text-[11px] text-muted-foreground font-normal">
                                                        {d.dispatch_date}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="p-3">
                                                <div className="font-semibold text-foreground">
                                                    {d.recipient_name}
                                                </div>
                                                {d.address && (
                                                    <div className="text-[11px] text-muted-foreground truncate max-w-sm">
                                                        {d.address}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="p-3">
                                                {d.invoice_code ? (
                                                    <div className="flex items-center gap-1 font-medium text-emerald-600 dark:text-emerald-400">
                                                        <FileText className="size-3.5" />
                                                        <span>{d.invoice_code}</span>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">—</span>
                                                )}
                                                {d.order_code && (
                                                    <div className="text-[11px] text-muted-foreground">
                                                        Ped: {d.order_code}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="p-3 text-right font-semibold tabular-nums">
                                                {Number(d.total_weight || 0).toLocaleString('es-VE', {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2,
                                                })}
                                            </td>
                                            <td className="p-3 text-right tabular-nums text-muted-foreground">
                                                {Number(d.total_volume || 0).toLocaleString('es-VE', {
                                                    minimumFractionDigits: 3,
                                                    maximumFractionDigits: 3,
                                                })}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                            <tfoot className="bg-muted/40 border-t font-semibold">
                                <tr>
                                    <td colSpan={4} className="p-3 text-right">
                                        Total Consolidado:
                                    </td>
                                    <td className="p-3 text-right font-bold tabular-nums">
                                        {Number(plan.total_weight).toLocaleString('es-VE', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2,
                                        })}{' '}
                                        kg
                                    </td>
                                    <td className="p-3 text-right font-bold tabular-nums">
                                        {Number(plan.total_volume).toLocaleString('es-VE', {
                                            minimumFractionDigits: 3,
                                            maximumFractionDigits: 3,
                                        })}{' '}
                                        m³
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
