import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Calendar,
    Check,
    ClipboardCheck,
    Edit,
    Paperclip,
    StickyNote,
    Warehouse,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useConfiguration } from '@/hooks/use-configuration';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import adjustments from '@/routes/adjustments';
import type { BreadcrumbItem } from '@/types';
import {
    DIRECTION_LABELS,
    formatAmount,
    isEditable,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    STATUS_TRANSITIONS,
    TYPE_LABELS,
    type Adjustment,
    type AdjustmentStatus,
} from './types/Adjustment';

interface Props {
    adjustment: Adjustment;
    /** El usuario puede firmar la aplicación del ajuste. */
    canApprove: boolean;
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

/** El botón con el que se avanza de estado dice lo que el paso significa. */
const ACTION_LABELS: Record<AdjustmentStatus, string> = {
    draft: 'Volver a borrador',
    pending_approval: 'Enviar a aprobación',
    confirmed: 'Aprobar y aplicar',
    completed: 'Cerrar',
    cancelled: 'Anular',
};

export default function AdjustmentsShow({ adjustment, canApprove }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const currency = useConfiguration()?.base_currency ?? 'USD';

    const [cancelOpen, setCancelOpen] = useState(false);

    const { data, setData, put, processing, errors, reset, transform } =
        useForm({ cancellation_reason: '' });

    const statusUrl = adjustments.updateStatus({
        company: companyId,
        id: adjustment.id,
    }).url;

    /** El error de transición llega en `status`, que no es campo del formulario. */
    const statusError = (errors as Record<string, string | undefined>).status;

    const transitions = STATUS_TRANSITIONS[adjustment.status] ?? [];
    const activeLines = (adjustment.lines ?? []).filter(
        (line) => line.status === 'active',
    );

    /** El estado destino viaja en el `transform`: la pantalla solo captura el motivo. */
    const advanceTo = (status: AdjustmentStatus) => {
        transform(() => ({ status, cancellation_reason: '' }));
        put(statusUrl);
    };

    const submitCancellation = () => {
        transform((current) => ({
            status: 'cancelled',
            cancellation_reason: current.cancellation_reason,
        }));

        put(statusUrl, {
            onSuccess: () => {
                setCancelOpen(false);
                reset();
            },
        });
    };

    const netCost = Number(adjustment.net_cost);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Ajustes',
            href: adjustments.index(companyId).url,
        },
        {
            title: adjustment.code,
            href: adjustments.show({ company: companyId, id: adjustment.id })
                .url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={adjustment.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={adjustments.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Ajustes
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {adjustment.code}
                            </h1>
                            <StatusPill
                                kind={STATUS_PILL_KIND[adjustment.status]}
                            >
                                {STATUS_LABELS[adjustment.status]}
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <ClipboardCheck className="size-3.5 opacity-80" />
                                {TYPE_LABELS[adjustment.type]}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Warehouse className="size-3.5 opacity-80" />
                                {adjustment.warehouse_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Calendar className="size-3.5 opacity-80" />
                                {adjustment.adjustment_date}
                            </span>
                            {adjustment.attachment_path && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Paperclip className="size-3.5 opacity-80" />
                                    {adjustment.attachment_path}
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
                                    disabled={
                                        processing ||
                                        (status === 'confirmed' && !canApprove)
                                    }
                                >
                                    <Check />
                                    {ACTION_LABELS[status]}
                                </Button>
                            ))}
                        {transitions.includes('cancelled') && (
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold text-bad"
                                onClick={() => setCancelOpen(true)}
                                disabled={processing}
                            >
                                <Ban />
                                Anular
                            </Button>
                        )}
                        {isEditable(adjustment.status) && (
                            <Link
                                href={
                                    adjustments.edit({
                                        company: companyId,
                                        id: adjustment.id,
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

                {adjustment.status === 'pending_approval' && !canApprove && (
                    <p className="rounded-[12px] border border-dashed p-4 text-[13px] text-muted-foreground">
                        Este ajuste espera la firma de alguien con permiso para
                        aprobarlo. Mientras tanto la existencia no ha cambiado.
                    </p>
                )}

                <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Documento
                        </div>
                        <DataRow
                            label="Tipo de ajuste"
                            value={TYPE_LABELS[adjustment.type]}
                        />
                        <DataRow
                            label="Dirección"
                            value={DIRECTION_LABELS[adjustment.direction]}
                        />
                        <DataRow
                            label="Conteo asociado"
                            value={adjustment.count_id ?? '—'}
                        />
                        <DataRow
                            label="Registrado por"
                            value={adjustment.created_by_name ?? '—'}
                        />
                        <DataRow
                            label="Aprobado por"
                            value={adjustment.approved_by_name ?? '—'}
                        />
                        <DataRow
                            label="Aprobado el"
                            value={adjustment.approved_at ?? '—'}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Impacto
                        </div>
                        <DataRow
                            label="Unidades que entran"
                            value={adjustment.total_quantity_in}
                        />
                        <DataRow
                            label="Unidades que salen"
                            value={adjustment.total_quantity_out}
                        />
                        <DataRow
                            label="Valor que entra"
                            value={formatAmount(
                                Number(adjustment.total_cost_in),
                                currency,
                            )}
                        />
                        <DataRow
                            label="Valor que sale"
                            value={formatAmount(
                                Number(adjustment.total_cost_out),
                                currency,
                            )}
                        />
                        <DataRow
                            label="Impacto neto"
                            value={
                                <span
                                    className={cn(
                                        'tabular-nums',
                                        netCost < 0
                                            ? 'text-bad'
                                            : netCost > 0
                                              ? 'text-ok'
                                              : '',
                                    )}
                                >
                                    {formatAmount(netCost, currency)}
                                </span>
                            }
                        />
                    </Card>
                </div>

                <Card className="gap-2 rounded-2xl px-[18px] py-4">
                    <div className="text-[13px] font-bold text-muted-foreground">
                        Motivo
                    </div>
                    <p className="text-sm leading-relaxed">
                        {adjustment.reason}
                    </p>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="border-b p-5 text-[13px] font-bold text-muted-foreground">
                        Líneas
                    </div>
                    <div className="hidden h-11 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.4fr_2.2fr_1.2fr_0.8fr_0.8fr_0.9fr_1fr_1fr]">
                        {[
                            '#',
                            'Artículo',
                            'Lote / serie',
                            'Sistema',
                            'Contado',
                            'Diferencia',
                            'Costo',
                            'Impacto',
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
                        {activeLines.map((line) => {
                            const difference = Number(line.difference_quantity);
                            const impact =
                                line.movement_type === 'adjustment_out'
                                    ? -Number(line.total_cost)
                                    : Number(line.total_cost);

                            return (
                                <div
                                    key={line.id}
                                    className="grid gap-3.5 border-b px-5 py-3 last:border-b-0 lg:grid-cols-[0.4fr_2.2fr_1.2fr_0.8fr_0.8fr_0.9fr_1fr_1fr] lg:items-center"
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
                                        {line.system_quantity}
                                    </div>
                                    <div className="text-[13.5px] tabular-nums">
                                        {line.counted_quantity}
                                    </div>
                                    <div
                                        className={cn(
                                            'text-[13.5px] font-semibold tabular-nums',
                                            difference < 0
                                                ? 'text-bad'
                                                : difference > 0
                                                  ? 'text-ok'
                                                  : '',
                                        )}
                                    >
                                        {difference > 0 ? '+' : ''}
                                        {line.difference_quantity}
                                    </div>
                                    <div className="text-[13.5px] tabular-nums">
                                        {line.unit_cost}
                                    </div>
                                    <div
                                        className={cn(
                                            'text-[13.5px] font-semibold tabular-nums',
                                            impact < 0
                                                ? 'text-bad'
                                                : impact > 0
                                                  ? 'text-ok'
                                                  : '',
                                        )}
                                    >
                                        {formatAmount(impact, currency)}
                                    </div>
                                </div>
                            );
                        })}
                        {activeLines.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                El ajuste no tiene líneas activas.
                            </div>
                        )}
                    </div>
                </Card>

                {activeLines.some((line) => line.reason) && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Motivos por línea
                        </div>
                        {activeLines
                            .filter((line) => line.reason)
                            .map((line) => (
                                <p
                                    key={line.id}
                                    className="text-sm leading-relaxed"
                                >
                                    <b>#{line.line_number}</b>{' '}
                                    {line.item_name ?? 'la línea'}:{' '}
                                    {line.reason}
                                </p>
                            ))}
                    </Card>
                )}

                {adjustment.cancellation_reason && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Ban className="size-[15px]" />
                            Motivo de anulación
                        </div>
                        <p className="text-sm leading-relaxed">
                            {adjustment.cancellation_reason}
                        </p>
                    </Card>
                )}

                {adjustment.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {adjustment.notes}
                        </p>
                    </Card>
                )}
            </div>

            <Dialog open={cancelOpen} onOpenChange={setCancelOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Anular ajuste</DialogTitle>
                        <DialogDescription>
                            El ajuste no se elimina: queda anulado con el motivo
                            que indiques, y si ya se había aplicado el kardex
                            recibe su contrapartida.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="cancellation_reason"
                            className="text-[13px] font-semibold"
                        >
                            Motivo *
                        </Label>
                        <Textarea
                            id="cancellation_reason"
                            value={data.cancellation_reason}
                            onChange={(e) =>
                                setData('cancellation_reason', e.target.value)
                            }
                            rows={3}
                            maxLength={500}
                            className="rounded-[10px]"
                        />
                        {errors.cancellation_reason && (
                            <p className="text-sm text-bad">
                                {errors.cancellation_reason}
                            </p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setCancelOpen(false)}
                            disabled={processing}
                        >
                            Volver
                        </Button>
                        <Button
                            onClick={submitCancellation}
                            disabled={processing}
                        >
                            Anular ajuste
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
