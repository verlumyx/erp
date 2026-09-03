import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Calendar,
    Check,
    ClipboardList,
    Edit,
    StickyNote,
    Truck,
    Warehouse,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { AmountDual } from '@/components/amount-dual';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import entries from '@/routes/entries';
import purchaseOrders from '@/routes/purchase-orders';
import transfers from '@/routes/transfers';
import type { BreadcrumbItem } from '@/types';
import {
    INSPECTION_LABELS,
    isEditable,
    PURCHASE_ORDER,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    STATUS_TRANSITIONS,
    TRANSFER,
    TYPE_LABELS,
    type Entry,
    type EntryLine,
    type EntryStatus,
} from './types/Entry';

interface Props {
    entry: Entry;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/** Los lotes activos de una línea, listados con lo que trajo cada uno. */
function lotLabel(line: EntryLine): string {
    const lots = (line.lots ?? []).filter((lot) => lot.status === 'active');

    if (lots.length === 0) {
        return '—';
    }

    return lots.map((lot) => `${lot.lot_number} (${lot.quantity})`).join(', ');
}

function serialCount(line: EntryLine): number {
    return (line.serials ?? []).filter((serial) => serial.status === 'active')
        .length;
}

function DataRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-4 text-[13.5px]">
            <span className="font-medium text-muted-foreground">{label}</span>
            <b className="text-right font-bold">{value}</b>
        </div>
    );
}

/**
 * Un importe de la entrada con su equivalente debajo. Las cuatro columnas de
 * moneda que congeló se atan aquí una vez, en vez de repetirlas en cada fila.
 */
function Amount({ model, value }: { model: Entry; value: string }) {
    return (
        <AmountDual
            amount={value}
            currency={model.currency}
            rate={model.exchange_rate}
            baseCurrency={model.base_currency}
            baseRate={model.base_exchange_rate}
            className="items-end"
        />
    );
}

export default function EntriesShow({ entry }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing, errors, transform } = useForm({ status: '' });

    const statusUrl = entries.updateStatus({
        company: companyId,
        id: entry.id,
    }).url;

    /** El error de transición llega en `status`, que aquí no se captura. */
    const statusError = (errors as Record<string, string | undefined>).status;

    const transitions = STATUS_TRANSITIONS[entry.status] ?? [];
    const activeLines = (entry.lines ?? []).filter(
        (line) => line.status === 'active',
    );

    /** El estado destino viaja en el `transform`: la pantalla no captura nada. */
    const advanceTo = (status: EntryStatus) => {
        transform(() => ({ status }));
        put(statusUrl);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Entradas',
            href: entries.index(companyId).url,
        },
        {
            title: entry.code,
            href: entries.show({ company: companyId, id: entry.id }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={entry.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={entries.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Entradas
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {entry.code}
                            </h1>
                            <StatusPill kind={STATUS_PILL_KIND[entry.status]}>
                                {STATUS_LABELS[entry.status]}
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Truck className="size-3.5 opacity-80" />
                                {entry.supplier_name ??
                                    TYPE_LABELS[entry.entry_type]}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Warehouse className="size-3.5 opacity-80" />
                                {entry.warehouse_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Calendar className="size-3.5 opacity-80" />
                                {entry.entry_date}
                            </span>
                            {entry.sourceable_type === PURCHASE_ORDER &&
                                entry.sourceable_id && (
                                    <Link
                                        href={
                                            purchaseOrders.show({
                                                company: companyId,
                                                id: entry.sourceable_id,
                                            }).url
                                        }
                                        className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground transition-colors hover:text-primary"
                                    >
                                        <ClipboardList className="size-3.5 opacity-80" />
                                        {entry.sourceable_code ??
                                            'Orden de compra'}
                                    </Link>
                                )}
                            {entry.sourceable_type === TRANSFER &&
                                entry.sourceable_id && (
                                    <Link
                                        href={
                                            transfers.show({
                                                company: companyId,
                                                id: entry.sourceable_id,
                                            }).url
                                        }
                                        className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground transition-colors hover:text-primary"
                                    >
                                        <ClipboardList className="size-3.5 opacity-80" />
                                        {entry.sourceable_code ?? 'Traslado'}
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
                        {isEditable(entry.status) && (
                            <Link
                                href={
                                    entries.edit({
                                        company: companyId,
                                        id: entry.id,
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
                            Documento
                        </div>
                        <DataRow
                            label="Tipo de entrada"
                            value={TYPE_LABELS[entry.entry_type]}
                        />
                        <DataRow
                            label={
                                entry.sourceable_type === TRANSFER
                                    ? 'Traslado de origen'
                                    : 'Orden de origen'
                            }
                            value={entry.sourceable_code ?? '—'}
                        />
                        <DataRow
                            label="Remisión del proveedor"
                            value={entry.supplier_document ?? '—'}
                        />
                        <DataRow
                            label="Facturada"
                            value={entry.is_invoiced === 'yes' ? 'Sí' : 'No'}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Recepción
                        </div>
                        <DataRow
                            label="Transportista"
                            value={entry.carrier ?? '—'}
                        />
                        <DataRow
                            label="Guía de transporte"
                            value={entry.tracking_number ?? '—'}
                        />
                        <DataRow
                            label="Recibido por"
                            value={entry.received_by_name ?? '—'}
                        />
                        <DataRow
                            label="Inspeccionado por"
                            value={entry.inspected_by_name ?? '—'}
                        />
                        <DataRow
                            label="Control de calidad"
                            value={INSPECTION_LABELS[entry.inspection_status]}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4 lg:col-span-2">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Costos
                        </div>
                        <DataRow
                            label="Moneda"
                            value={`${entry.currency} (tasa ${entry.exchange_rate})`}
                        />
                        <DataRow
                            label="Moneda de la empresa"
                            value={
                                entry.base_currency
                                    ? `${entry.base_currency} (tasa ${entry.base_exchange_rate})`
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Unidades ingresadas"
                            value={entry.total_quantity}
                        />
                        <DataRow
                            label="Valor ingresado"
                            value={
                                <Amount
                                    model={entry}
                                    value={entry.total_cost}
                                />
                            }
                        />
                    </Card>
                </div>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="border-b p-5 text-[13px] font-bold text-muted-foreground">
                        Líneas
                    </div>
                    <div className="hidden h-11 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.4fr_2fr_1.4fr_0.8fr_0.8fr_0.8fr_1fr_1fr]">
                        {[
                            '#',
                            'Artículo',
                            'Lotes / series',
                            'Ordenado',
                            'Llegó',
                            'Aceptado',
                            'Costo final',
                            'Total',
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
                                className="grid gap-3.5 border-b px-5 py-3 last:border-b-0 lg:grid-cols-[0.4fr_2fr_1.4fr_0.8fr_0.8fr_0.8fr_1fr_1fr] lg:items-center"
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
                                        {Number(line.rejected_quantity) > 0
                                            ? ` · Rechazado ${line.rejected_quantity}`
                                            : ''}
                                    </span>
                                </div>
                                <div className="flex min-w-0 flex-col text-[13px] text-muted-foreground">
                                    <span className="truncate">
                                        {lotLabel(line)}
                                    </span>
                                    {serialCount(line) > 0 && (
                                        <span className="truncate text-[12.5px]">
                                            {serialCount(line)} serie
                                            {serialCount(line) !== 1 ? 's' : ''}
                                        </span>
                                    )}
                                </div>
                                {/** Lo que pidió la orden; vacío en una línea suelta. */}
                                <div className="text-[13.5px] text-muted-foreground tabular-nums">
                                    {line.source_quantity ?? '—'}
                                </div>
                                <div className="text-[13.5px] tabular-nums">
                                    {line.quantity}
                                </div>
                                <div className="text-[13.5px] tabular-nums">
                                    {line.received_quantity}
                                </div>
                                <div className="text-[13.5px] tabular-nums">
                                    {line.landed_cost}
                                </div>
                                <div className="text-[13.5px] font-semibold tabular-nums">
                                    {line.total}
                                </div>
                            </div>
                        ))}
                        {activeLines.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                La entrada no tiene líneas activas.
                            </div>
                        )}
                    </div>
                </Card>

                {activeLines.some((line) => line.rejection_reason) && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Rechazos de la inspección
                        </div>
                        {activeLines
                            .filter((line) => line.rejection_reason)
                            .map((line) => (
                                <p
                                    key={line.id}
                                    className="text-sm leading-relaxed"
                                >
                                    <b>#{line.line_number}</b>{' '}
                                    {line.rejected_quantity} de{' '}
                                    {line.item_name ?? 'la línea'}:{' '}
                                    {line.rejection_reason}
                                </p>
                            ))}
                    </Card>
                )}

                {entry.cancelled_at && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Ban className="size-[15px]" />
                            Anulada
                        </div>
                        <p className="text-sm leading-relaxed">
                            El {entry.cancelled_at}. El documento se conserva y
                            el kardex recibió su contrapartida: no se elimina
                            nada.
                        </p>
                    </Card>
                )}

                {entry.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {entry.notes}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
