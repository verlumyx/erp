import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    Ban,
    Calendar,
    Check,
    Edit,
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
import transferRoutes from '@/routes/transfers';
import type { BreadcrumbItem } from '@/types';
import {
    formatAmount,
    isEditable,
    MOVEMENT_PILL_KIND,
    MOVEMENT_STATUS_LABELS,
    REASON_LABELS,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    STATUS_TRANSITIONS,
    type Transfer,
    type TransferStatus,
} from './types/Transfer';

interface Props {
    transfer: Transfer;
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

export default function TransfersShow({ transfer }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const configuration = useConfiguration();

    /** Los importes del traslado son de costo: van en la moneda de la empresa. */
    const currency = configuration?.base_currency ?? 'USD';

    const { put, processing, errors, transform } = useForm({ status: '' });

    const statusUrl = transferRoutes.updateStatus({
        company: companyId,
        id: transfer.id,
    }).url;

    /** El error de transición llega en `status`, que aquí no se captura. */
    const statusError = (errors as Record<string, string | undefined>).status;

    const transitions = STATUS_TRANSITIONS[transfer.status] ?? [];
    const activeLines = (transfer.lines ?? []).filter(
        (line) => line.status === 'active',
    );

    /** El estado destino viaja en el `transform`: la pantalla no captura nada. */
    const advanceTo = (status: TransferStatus) => {
        transform(() => ({ status }));
        put(statusUrl);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Traslados',
            href: transferRoutes.index(companyId).url,
        },
        {
            title: transfer.code,
            href: transferRoutes.show({
                company: companyId,
                id: transfer.id,
            }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={transfer.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={transferRoutes.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Traslados
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {transfer.code}
                            </h1>
                            <StatusPill
                                kind={STATUS_PILL_KIND[transfer.status]}
                            >
                                {STATUS_LABELS[transfer.status]}
                            </StatusPill>
                            <StatusPill
                                kind={
                                    MOVEMENT_PILL_KIND[transfer.transfer_status]
                                }
                            >
                                {
                                    MOVEMENT_STATUS_LABELS[
                                        transfer.transfer_status
                                    ]
                                }
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Warehouse className="size-3.5 opacity-80" />
                                {transfer.origin_warehouse_name ?? '—'}
                                <ArrowRight className="size-3.5 opacity-60" />
                                {transfer.destination_warehouse_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Calendar className="size-3.5 opacity-80" />
                                {transfer.transfer_date}
                            </span>
                            {transfer.driver_name && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Truck className="size-3.5 opacity-80" />
                                    {transfer.driver_name}
                                    {transfer.vehicle_plate
                                        ? ` · ${transfer.vehicle_plate}`
                                        : ''}
                                </span>
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
                        {isEditable(transfer.status) && (
                            <Link
                                href={
                                    transferRoutes.edit({
                                        company: companyId,
                                        id: transfer.id,
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
                            El viaje
                        </div>
                        <DataRow
                            label="Motivo"
                            value={REASON_LABELS[transfer.reason]}
                        />
                        <DataRow
                            label="Detalle"
                            value={transfer.reason_detail ?? '—'}
                        />
                        <DataRow
                            label="Llegada estimada"
                            value={transfer.expected_date ?? '—'}
                        />
                        <DataRow
                            label="Llegada efectiva"
                            value={transfer.received_date ?? '—'}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            La carga
                        </div>
                        <DataRow
                            label="Unidades"
                            value={transfer.total_quantity}
                        />
                        <DataRow
                            label="Valor trasladado"
                            value={formatAmount(transfer.total_cost, currency)}
                        />
                        <DataRow
                            label="Despachó"
                            value={transfer.sent_by_name ?? '—'}
                        />
                        <DataRow
                            label="Recibió"
                            value={transfer.received_by_name ?? '—'}
                        />
                    </Card>
                </div>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="border-b p-5 text-[13px] font-bold text-muted-foreground">
                        Líneas
                    </div>
                    <div className="hidden h-11 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.4fr_3.4fr_1fr_1fr]">
                        {['#', 'Artículo', 'Cantidad', 'Costo'].map(
                            (header) => (
                                <div
                                    key={header}
                                    className="text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase"
                                >
                                    {header}
                                </div>
                            ),
                        )}
                    </div>
                    <div className="flex flex-col">
                        {activeLines.map((line) => (
                            <div
                                key={line.id}
                                className="grid gap-3.5 border-b px-5 py-3 last:border-b-0 lg:grid-cols-[0.4fr_3.4fr_1fr_1fr] lg:items-center"
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
                                    </span>
                                </div>
                                <div className="text-[13.5px] tabular-nums">
                                    {line.quantity}
                                </div>
                                <div className="text-[13.5px] font-semibold tabular-nums">
                                    {line.unit_cost}
                                </div>
                            </div>
                        ))}
                        {activeLines.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                El traslado no tiene líneas activas.
                            </div>
                        )}
                    </div>
                </Card>

                {transfer.cancelled_at && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Ban className="size-[15px]" />
                            Anulado
                        </div>
                        <p className="text-sm leading-relaxed">
                            El {transfer.cancelled_at}. El documento se conserva
                            y el kardex recibió sus contrapartidas: la mercancía
                            volvió a estar donde estaba y no se elimina nada.
                        </p>
                    </Card>
                )}

                {transfer.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {transfer.notes}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
