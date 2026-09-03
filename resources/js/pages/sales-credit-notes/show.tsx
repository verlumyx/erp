import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Calendar,
    Check,
    Contact,
    Edit,
    Hash,
    ReceiptText,
    StickyNote,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { AmountDual } from '@/components/amount-dual';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import salesCreditNotes from '@/routes/sales-credit-notes';
import salesInvoices from '@/routes/sales-invoices';
import type { BreadcrumbItem } from '@/types';
import {
    isEditable,
    REASON_LABELS,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    STATUS_TRANSITIONS,
    type SalesCreditNote,
    type SalesCreditNoteStatus,
} from './types/SalesCreditNote';

interface Props {
    salesCreditNote: SalesCreditNote;
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

/**
 * Un importe de la nota con su equivalente debajo. Las cuatro columnas de
 * moneda que congeló la nota se atan aquí una vez, en vez de repetirlas en cada
 * fila.
 */
function Amount({ note, value }: { note: SalesCreditNote; value: string }) {
    return (
        <AmountDual
            amount={value}
            currency={note.currency}
            rate={note.exchange_rate}
            baseCurrency={note.base_currency}
            baseRate={note.base_exchange_rate}
            className="items-end"
        />
    );
}

export default function SalesCreditNotesShow({ salesCreditNote }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing, errors, transform } = useForm({ status: '' });

    const statusUrl = salesCreditNotes.updateStatus({
        company: companyId,
        id: salesCreditNote.id,
    }).url;

    /** El error de transición llega en `status`, que aquí no se captura. */
    const statusError = (errors as Record<string, string | undefined>).status;

    const transitions = STATUS_TRANSITIONS[salesCreditNote.status] ?? [];
    const activeLines = (salesCreditNote.lines ?? []).filter(
        (line) => line.status === 'active',
    );

    /** El estado destino viaja en el `transform`: la pantalla no captura nada. */
    const advanceTo = (status: SalesCreditNoteStatus) => {
        transform(() => ({ status }));
        put(statusUrl);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Notas de crédito a cliente',
            href: salesCreditNotes.index(companyId).url,
        },
        {
            title: salesCreditNote.code,
            href: salesCreditNotes.show({
                company: companyId,
                id: salesCreditNote.id,
            }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={salesCreditNote.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={salesCreditNotes.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Notas de crédito a cliente
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {salesCreditNote.code}
                            </h1>
                            <StatusPill
                                kind={STATUS_PILL_KIND[salesCreditNote.status]}
                            >
                                {STATUS_LABELS[salesCreditNote.status]}
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Contact className="size-3.5 opacity-80" />
                                {salesCreditNote.client_name ?? '—'}
                            </span>
                            {salesCreditNote.note_number && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {salesCreditNote.note_series
                                        ? `${salesCreditNote.note_series}-${salesCreditNote.note_number}`
                                        : salesCreditNote.note_number}
                                </span>
                            )}
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Calendar className="size-3.5 opacity-80" />
                                {salesCreditNote.note_date}
                            </span>
                            {salesCreditNote.sales_invoice_id && (
                                <Link
                                    href={
                                        salesInvoices.show({
                                            company: companyId,
                                            id: salesCreditNote.sales_invoice_id,
                                        }).url
                                    }
                                    className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground transition-colors hover:text-primary"
                                >
                                    <ReceiptText className="size-3.5 opacity-80" />
                                    {salesCreditNote.sales_invoice_code ??
                                        'Factura de venta'}
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
                        {isEditable(salesCreditNote.status) && (
                            <Link
                                href={
                                    salesCreditNotes.edit({
                                        company: companyId,
                                        id: salesCreditNote.id,
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
                            label="Motivo"
                            value={REASON_LABELS[salesCreditNote.reason]}
                        />
                        <DataRow
                            label="Serie fiscal"
                            value={salesCreditNote.note_series ?? '—'}
                        />
                        <DataRow
                            label="Correlativo fiscal"
                            value={
                                salesCreditNote.note_number ??
                                'Se asigna al confirmar'
                            }
                        />
                        <DataRow
                            label="Factura afectada"
                            value={salesCreditNote.sales_invoice_code ?? '—'}
                        />
                        {salesCreditNote.reason_detail && (
                            <DataRow
                                label="Detalle"
                                value={salesCreditNote.reason_detail}
                            />
                        )}
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Importes
                        </div>
                        <DataRow
                            label="Moneda"
                            value={`${salesCreditNote.currency} (tasa ${salesCreditNote.exchange_rate})`}
                        />
                        <DataRow
                            label="Moneda de la empresa"
                            value={
                                salesCreditNote.base_currency
                                    ? `${salesCreditNote.base_currency} (tasa ${salesCreditNote.base_exchange_rate})`
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Subtotal"
                            value={
                                <Amount
                                    note={salesCreditNote}
                                    value={salesCreditNote.subtotal}
                                />
                            }
                        />
                        <DataRow
                            label="Impuesto"
                            value={
                                <Amount
                                    note={salesCreditNote}
                                    value={salesCreditNote.tax_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Total"
                            value={
                                <Amount
                                    note={salesCreditNote}
                                    value={salesCreditNote.total}
                                />
                            }
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Crédito
                        </div>
                        <DataRow
                            label="Aplicado a facturas"
                            value={
                                <Amount
                                    note={salesCreditNote}
                                    value={salesCreditNote.applied_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Disponible"
                            value={
                                <Amount
                                    note={salesCreditNote}
                                    value={salesCreditNote.balance}
                                />
                            }
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Importes en bolívares
                        </div>
                        <p className="text-[12.5px] text-muted-foreground">
                            Congelados al emitir: la nota es un documento fiscal
                            y su valor en bolívares no se recalcula.
                        </p>
                        <DataRow
                            label="Subtotal"
                            value={salesCreditNote.subtotal_ves}
                        />
                        <DataRow
                            label="Impuesto"
                            value={salesCreditNote.tax_amount_ves}
                        />
                        <DataRow
                            label="Total"
                            value={salesCreditNote.total_ves}
                        />
                    </Card>
                </div>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="border-b p-5 text-[13px] font-bold text-muted-foreground">
                        Líneas
                    </div>
                    <div className="hidden h-11 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.4fr_2.4fr_1fr_1fr_1fr]">
                        {['#', 'Artículo', 'Cantidad', 'Precio', 'Total'].map(
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
                                className="grid gap-3.5 border-b px-5 py-3 last:border-b-0 lg:grid-cols-[0.4fr_2.4fr_1fr_1fr_1fr] lg:items-center"
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
                                <div className="text-[13.5px] tabular-nums">
                                    {line.unit_price}
                                </div>
                                <div className="text-[13.5px] font-semibold tabular-nums">
                                    {line.total}
                                </div>
                            </div>
                        ))}
                        {activeLines.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                La nota no tiene líneas activas.
                            </div>
                        )}
                    </div>
                </Card>

                {salesCreditNote.cancelled_at && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Ban className="size-[15px]" />
                            Anulada
                        </div>
                        <p className="text-sm leading-relaxed">
                            El {salesCreditNote.cancelled_at}. El documento se
                            conserva: no se elimina nada.
                        </p>
                    </Card>
                )}

                {salesCreditNote.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {salesCreditNote.notes}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
