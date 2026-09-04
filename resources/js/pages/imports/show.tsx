import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Calendar,
    Check,
    ClipboardCheck,
    Edit,
    Ship,
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
import AppLayout from '@/layouts/app-layout';
import adjustments from '@/routes/adjustments';
import imports from '@/routes/imports';
import type { BreadcrumbItem } from '@/types';
import {
    ALLOCATION_METHOD_LABELS,
    CONCEPT_LABELS,
    formatAmount,
    isEditable,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    STATUS_TRANSITIONS,
    type Import,
    type ImportStatus,
} from './types/Import';

interface Props {
    import: Import;
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
const ACTION_LABELS: Record<ImportStatus, string> = {
    draft: 'Volver a borrador',
    confirmed: 'Confirmar y generar el ajuste',
    completed: 'Cerrar',
    cancelled: 'Anular',
};

export default function ImportsShow({ import: model }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [cancelOpen, setCancelOpen] = useState(false);

    const { data, setData, put, processing, errors, reset, transform } =
        useForm({ cancellation_reason: '' });

    const statusUrl = imports.updateStatus({
        company: companyId,
        id: model.id,
    }).url;

    /** El error de transición llega en `status`, que no es campo del formulario. */
    const statusError = (errors as Record<string, string | undefined>).status;

    const transitions = STATUS_TRANSITIONS[model.status] ?? [];
    const costs = (model.costs ?? []).filter(
        (cost) => cost.status === 'active',
    );
    const entries = (model.entries ?? []).filter(
        (entry) => entry.status === 'active',
    );
    const lines = (model.lines ?? []).filter(
        (line) => line.status === 'active',
    );

    /** El estado destino viaja en el `transform`: la pantalla solo captura el motivo. */
    const advanceTo = (status: ImportStatus) => {
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

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Importaciones', href: imports.index(companyId).url },
        {
            title: model.code,
            href: imports.show({ company: companyId, id: model.id }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={model.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={imports.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Importaciones
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {model.code}
                            </h1>
                            <StatusPill kind={STATUS_PILL_KIND[model.status]}>
                                {STATUS_LABELS[model.status]}
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Ship className="size-3.5 opacity-80" />
                                {model.reference ?? 'Sin referencia'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Warehouse className="size-3.5 opacity-80" />
                                {model.warehouse_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Calendar className="size-3.5 opacity-80" />
                                {model.import_date}
                            </span>
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
                        {isEditable(model.status) && (
                            <Link
                                href={
                                    imports.edit({
                                        company: companyId,
                                        id: model.id,
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

                {model.adjustment_id && (
                    <Card className="flex-row flex-wrap items-center justify-between gap-4 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2.5">
                            <ClipboardCheck className="size-[18px] text-primary" />
                            <div>
                                <div className="text-[14px] font-bold">
                                    Ajuste de revaluación{' '}
                                    {model.adjustment_code}
                                </div>
                                <p className="text-[12.5px] text-muted-foreground">
                                    El expediente no toca el kardex: lo toca
                                    este ajuste, y hasta que se confirme el
                                    inventario sigue valorado como estaba.
                                </p>
                            </div>
                        </div>
                        <Link
                            href={
                                adjustments.show({
                                    company: companyId,
                                    id: model.adjustment_id,
                                }).url
                            }
                        >
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            >
                                Ver el ajuste
                            </Button>
                        </Link>
                    </Card>
                )}

                <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Expediente
                        </div>
                        <DataRow
                            label="Reparto"
                            value={
                                ALLOCATION_METHOD_LABELS[
                                    model.allocation_method
                                ]
                            }
                        />
                        <DataRow
                            label="Llegada del embarque"
                            value={model.arrival_date ?? '—'}
                        />
                        <DataRow label="Moneda" value={model.currency} />
                        <DataRow
                            label="Tasa congelada"
                            value={model.exchange_rate}
                        />
                        <DataRow
                            label="Registrado por"
                            value={model.created_by_name ?? '—'}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Costeo
                        </div>
                        <DataRow
                            label="Valor de la mercancía"
                            value={formatAmount(
                                Number(model.total_base_value),
                                model.currency,
                            )}
                        />
                        <DataRow
                            label="Gasto repartido"
                            value={formatAmount(
                                Number(model.total_charges),
                                model.currency,
                            )}
                        />
                        <DataRow
                            label="Puesto en bodega"
                            value={formatAmount(
                                Number(model.total_landed_value),
                                model.currency,
                            )}
                        />
                        <DataRow
                            label="Va al inventario"
                            value={
                                <span className="text-ok">
                                    {formatAmount(
                                        Number(model.capitalized_amount),
                                        model.currency,
                                    )}
                                </span>
                            }
                        />
                        <DataRow
                            label="Va a gasto"
                            value={
                                <span className="text-warn">
                                    {formatAmount(
                                        Number(model.variance_amount),
                                        model.currency,
                                    )}
                                </span>
                            }
                        />
                    </Card>
                </div>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="border-b p-5 text-base font-bold tracking-tight">
                        Costos
                    </div>
                    <div className="flex flex-col">
                        {costs.map((cost) => (
                            <div
                                key={cost.id}
                                className="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-3 last:border-b-0"
                            >
                                <div className="min-w-0">
                                    <div className="font-bold">
                                        {CONCEPT_LABELS[cost.concept]}
                                        {cost.description
                                            ? ` · ${cost.description}`
                                            : ''}
                                    </div>
                                    <div className="text-[12.5px] text-muted-foreground">
                                        {[
                                            cost.supplier_name,
                                            cost.sourceable_code,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ') || 'Sin documento'}
                                    </div>
                                </div>
                                <div className="text-right">
                                    <div className="font-bold tabular-nums">
                                        {formatAmount(
                                            Number(cost.converted_amount),
                                            model.currency,
                                        )}
                                    </div>
                                    {cost.currency !== model.currency && (
                                        <div className="text-[12.5px] text-muted-foreground tabular-nums">
                                            {formatAmount(
                                                Number(cost.amount),
                                                cost.currency,
                                            )}{' '}
                                            × {cost.exchange_rate}
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                        {costs.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                El expediente no tiene costos activos.
                            </div>
                        )}
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="border-b p-5 text-base font-bold tracking-tight">
                        Recepciones
                    </div>
                    <div className="flex flex-col">
                        {entries.map((entry) => (
                            <div
                                key={entry.id}
                                className="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-3 last:border-b-0"
                            >
                                <div>
                                    <div className="font-bold">
                                        {entry.entry_code ?? entry.entry_id}
                                    </div>
                                    <div className="text-[12.5px] text-muted-foreground">
                                        {[
                                            entry.entry_date,
                                            entry.supplier_document,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </div>
                                </div>
                                <div className="text-[13.5px] font-semibold tabular-nums">
                                    {entry.total_cost
                                        ? formatAmount(
                                              Number(entry.total_cost),
                                              model.currency,
                                          )
                                        : '—'}
                                </div>
                            </div>
                        ))}
                        {entries.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                El expediente no tiene recepciones activas.
                            </div>
                        )}
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="border-b p-5 text-base font-bold tracking-tight">
                        Ítems
                    </div>
                    <div className="flex flex-col">
                        {lines.map((line) => {
                            const lots = (line.lots ?? []).filter(
                                (lot) => lot.status === 'active',
                            );

                            return (
                                <div
                                    key={line.id}
                                    className="flex flex-col gap-2 border-b px-5 py-3 last:border-b-0"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <div className="min-w-0">
                                            <div className="font-bold">
                                                {line.item_code
                                                    ? `${line.item_code} — ${line.item_name}`
                                                    : line.item_name}
                                            </div>
                                            <div className="text-[12.5px] text-muted-foreground">
                                                {line.base_quantity} aceptadas ·{' '}
                                                {line.remaining_quantity} en
                                                bodega
                                                {line.location_name
                                                    ? ` · ${line.location_name}`
                                                    : ''}
                                            </div>
                                        </div>
                                        <div className="text-right text-[13.5px] tabular-nums">
                                            <div>
                                                {formatAmount(
                                                    Number(line.unit_cost),
                                                    model.currency,
                                                )}{' '}
                                                →{' '}
                                                <b>
                                                    {formatAmount(
                                                        Number(
                                                            line.new_unit_cost,
                                                        ),
                                                        model.currency,
                                                    )}
                                                </b>
                                            </div>
                                            <div className="text-[12.5px] text-muted-foreground">
                                                Gasto{' '}
                                                {formatAmount(
                                                    Number(
                                                        line.allocated_amount,
                                                    ),
                                                    model.currency,
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    {lots.length > 0 && (
                                        <div className="flex flex-wrap gap-2">
                                            {lots.map((lot) => (
                                                <span
                                                    key={lot.id}
                                                    className="rounded-[8px] bg-muted px-2 py-1 text-[12px] tabular-nums"
                                                >
                                                    {lot.lot_number ?? 'Lote'}:{' '}
                                                    {lot.remaining_quantity}/
                                                    {lot.base_quantity} →{' '}
                                                    {formatAmount(
                                                        Number(
                                                            lot.new_unit_cost,
                                                        ),
                                                        model.currency,
                                                    )}
                                                </span>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                        {lines.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                El expediente no tiene ítems activos.
                            </div>
                        )}
                    </div>
                </Card>

                {model.cancellation_reason && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Ban className="size-[15px]" />
                            Motivo de anulación
                        </div>
                        <p className="text-sm leading-relaxed">
                            {model.cancellation_reason}
                        </p>
                    </Card>
                )}

                {model.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {model.notes}
                        </p>
                    </Card>
                )}
            </div>

            <Dialog open={cancelOpen} onOpenChange={setCancelOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Anular expediente</DialogTitle>
                        <DialogDescription>
                            El expediente no se elimina: queda anulado con el
                            motivo que indiques, y el ajuste que hubiera
                            generado se anula con él. Si ese ajuste ya se
                            aplicó, hay que anularlo primero.
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
                            Anular expediente
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
