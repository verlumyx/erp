import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Calendar,
    Check,
    ClipboardList,
    Contact,
    Edit,
    MapPin,
    StickyNote,
    Truck,
    Warehouse,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useConfiguration } from '@/hooks/use-configuration';
import AppLayout from '@/layouts/app-layout';
import dispatchRoutes from '@/routes/dispatches';
import salesOrders from '@/routes/sales-orders';
import type { BreadcrumbItem } from '@/types';
import { DispatchDeliveryCard } from './components/DispatchDeliveryCard';
import {
    canRegisterDelivery,
    DELIVERY_PILL_KIND,
    DELIVERY_STATUS_LABELS,
    formatAmount,
    isEditable,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    STATUS_TRANSITIONS,
    type Dispatch,
    type DispatchStatus,
} from './types/Dispatch';

interface Props {
    dispatch: Dispatch;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function DataRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-4 text-[13.5px]">
            <span className="font-medium text-muted-foreground">{label}</span>
            <b className="text-right font-bold">{value}</b>
        </div>
    );
}

export default function DispatchesShow({ dispatch }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const configuration = useConfiguration();

    /** Los importes del despacho son de costo: van en la moneda de la empresa. */
    const currency = configuration?.base_currency ?? 'USD';

    const { put, processing, errors, transform } = useForm({ status: '' });

    const statusUrl = dispatchRoutes.updateStatus({
        company: companyId,
        id: dispatch.id,
    }).url;

    /** El error de transición llega en `status`, que aquí no se captura. */
    const statusError = (errors as Record<string, string | undefined>).status;

    const transitions = STATUS_TRANSITIONS[dispatch.status] ?? [];
    const activeLines = (dispatch.lines ?? []).filter(
        (line) => line.status === 'active',
    );

    /** El estado destino viaja en el `transform`: la pantalla no captura nada. */
    const advanceTo = (status: DispatchStatus) => {
        transform(() => ({ status }));
        put(statusUrl);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Despachos',
            href: dispatchRoutes.index(companyId).url,
        },
        {
            title: dispatch.code,
            href: dispatchRoutes.show({
                company: companyId,
                id: dispatch.id,
            }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={dispatch.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={dispatchRoutes.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Despachos
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {dispatch.code}
                            </h1>
                            <StatusPill
                                kind={STATUS_PILL_KIND[dispatch.status]}
                            >
                                {STATUS_LABELS[dispatch.status]}
                            </StatusPill>
                            <StatusPill
                                kind={
                                    DELIVERY_PILL_KIND[dispatch.delivery_status]
                                }
                            >
                                {
                                    DELIVERY_STATUS_LABELS[
                                        dispatch.delivery_status
                                    ]
                                }
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Contact className="size-3.5 opacity-80" />
                                {dispatch.client_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Warehouse className="size-3.5 opacity-80" />
                                {dispatch.warehouse_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Calendar className="size-3.5 opacity-80" />
                                {dispatch.dispatch_date}
                            </span>
                            {dispatch.driver_name && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Truck className="size-3.5 opacity-80" />
                                    {dispatch.driver_name}
                                    {dispatch.vehicle_plate
                                        ? ` · ${dispatch.vehicle_plate}`
                                        : ''}
                                </span>
                            )}
                            {dispatch.sourceable_id && (
                                <Link
                                    href={
                                        salesOrders.show({
                                            company: companyId,
                                            id: dispatch.sourceable_id,
                                        }).url
                                    }
                                    className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground transition-colors hover:text-primary"
                                >
                                    <ClipboardList className="size-3.5 opacity-80" />
                                    {dispatch.sourceable_code ??
                                        'Pedido de venta'}
                                </Link>
                            )}
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        {transitions
                            .filter((status) => status !== 'cancelled')
                            .map((status) => (
                                <Button
                                    key={status}
                                    variant="outline"
                                    className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                    onClick={() => advanceTo(status)}
                                    disabled={processing}
                                >
                                    <Check />
                                    {STATUS_LABELS[status]}
                                </Button>
                            ))}
                        {transitions.includes('cancelled') && (
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold text-bad"
                                onClick={() => advanceTo('cancelled')}
                                disabled={processing}
                            >
                                <Ban />
                                Anular
                            </Button>
                        )}
                        {isEditable(dispatch.status) && (
                            <Link
                                href={
                                    dispatchRoutes.edit({
                                        company: companyId,
                                        id: dispatch.id,
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
                        )}
                    </div>
                </Card>

                {statusError && (
                    <p className="text-sm text-bad">{statusError}</p>
                )}

                <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Transporte
                        </div>
                        <DataRow
                            label="Ruta"
                            value={
                                dispatch.route_id
                                    ? `${dispatch.route_code ?? ''} ${dispatch.route_name ?? ''}`.trim()
                                    : 'Sin ruta asignada'
                            }
                        />
                        <DataRow
                            label="Parada"
                            value={
                                dispatch.route_stop_id
                                    ? 'Asignada en el recorrido'
                                    : 'Sin planificar'
                            }
                        />
                        <DataRow
                            label="Conductor"
                            value={dispatch.driver_name ?? '—'}
                        />
                        <DataRow
                            label="Placa"
                            value={dispatch.vehicle_plate ?? '—'}
                        />
                        <DataRow
                            label="Transportista"
                            value={dispatch.carrier ?? '—'}
                        />
                        <DataRow
                            label="Guía"
                            value={dispatch.tracking_number ?? '—'}
                        />
                        <DataRow
                            label="Flete"
                            value={formatAmount(
                                dispatch.freight_amount,
                                currency,
                            )}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Carga
                        </div>
                        <DataRow
                            label="Unidades"
                            value={dispatch.total_quantity}
                        />
                        <DataRow label="Peso" value={dispatch.total_weight} />
                        <DataRow
                            label="Volumen"
                            value={dispatch.total_volume}
                        />
                        <DataRow
                            label="Costo de la mercancía"
                            value={formatAmount(dispatch.total_cost, currency)}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4 lg:col-span-2">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Entrega
                        </div>
                        <DataRow
                            label="Dirección"
                            value={dispatch.client_address_name ?? '—'}
                        />
                        <DataRow
                            label="Fecha de entrega"
                            value={dispatch.delivery_date ?? '—'}
                        />
                        <DataRow
                            label="Recibido por"
                            value={
                                dispatch.received_by_name
                                    ? `${dispatch.received_by_name}${
                                          dispatch.received_by_document
                                              ? ` · ${dispatch.received_by_document}`
                                              : ''
                                      }`
                                    : '—'
                            }
                        />
                        {dispatch.rejection_reason && (
                            <DataRow
                                label="Motivo del rechazo"
                                value={dispatch.rejection_reason}
                            />
                        )}
                        {dispatch.latitude && dispatch.longitude && (
                            <DataRow
                                label="Georreferencia"
                                value={
                                    <span className="inline-flex items-center gap-1.5">
                                        <MapPin className="size-3.5 opacity-80" />
                                        {dispatch.latitude},{' '}
                                        {dispatch.longitude}
                                    </span>
                                }
                            />
                        )}
                    </Card>
                </div>

                {canRegisterDelivery(dispatch) && (
                    <DispatchDeliveryCard dispatch={dispatch} />
                )}

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="border-b p-5 text-[13px] font-bold text-muted-foreground">
                        Líneas
                    </div>
                    <div className="hidden h-11 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.4fr_2.2fr_1.2fr_1fr_1fr_1fr]">
                        {[
                            '#',
                            'Artículo',
                            'Lote / serie',
                            'Salió',
                            'Entregado',
                            'Costo',
                        ].map((header) => (
                            <div
                                key={header}
                                className="text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase"
                            >
                                {header}
                            </div>
                        ))}
                    </div>
                    <div className="flex flex-col">
                        {activeLines.map((line) => (
                            <div
                                key={line.id}
                                className="grid gap-3.5 border-b px-5 py-3 last:border-b-0 lg:grid-cols-[0.4fr_2.2fr_1.2fr_1fr_1fr_1fr] lg:items-center"
                            >
                                <div className="text-[13.5px] font-semibold text-muted-foreground tabular-nums">
                                    {line.line_number}
                                </div>
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {line.item_name ?? '—'}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {line.item_code}
                                        {line.measurement_unit_name
                                            ? ` · ${line.measurement_unit_name}`
                                            : ''}
                                        {line.location_name
                                            ? ` · ${line.location_name}`
                                            : ''}
                                    </span>
                                </div>
                                <div className="truncate text-[13px] text-muted-foreground">
                                    {line.lot_number ?? '—'}
                                    {line.serial_number
                                        ? ` / ${line.serial_number}`
                                        : ''}
                                </div>
                                <div className="text-[13.5px] tabular-nums">
                                    {line.quantity}
                                </div>
                                <div className="text-[13.5px] tabular-nums">
                                    {line.delivered_quantity}
                                    {Number(line.returned_quantity) > 0
                                        ? ` (devolvió ${line.returned_quantity})`
                                        : ''}
                                </div>
                                <div className="text-[13.5px] font-semibold tabular-nums">
                                    {line.unit_cost}
                                </div>
                            </div>
                        ))}
                        {activeLines.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                El despacho no tiene líneas activas.
                            </div>
                        )}
                    </div>
                </Card>

                {dispatch.cancelled_at && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Ban className="size-[15px]" />
                            Anulado
                        </div>
                        <p className="text-sm leading-relaxed">
                            El {dispatch.cancelled_at}. El documento se conserva
                            y el kardex recibió su contrapartida: no se elimina
                            nada.
                        </p>
                    </Card>
                )}

                {dispatch.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {dispatch.notes}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
