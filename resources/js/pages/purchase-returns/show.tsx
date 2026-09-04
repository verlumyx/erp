import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Calendar,
    Check,
    Edit,
    FileMinus,
    ReceiptText,
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
import purchaseCreditNotes from '@/routes/purchase-credit-notes';
import purchaseInvoices from '@/routes/purchase-invoices';
import purchaseReturns from '@/routes/purchase-returns';
import type { BreadcrumbItem } from '@/types';
import {
    isEditable,
    REASON_LABELS,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    STATUS_TRANSITIONS,
    type PurchaseReturn,
    type PurchaseReturnStatus,
} from './types/PurchaseReturn';

interface Props {
    purchaseReturn: PurchaseReturn;
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
 * Un importe de la devolución con su equivalente debajo. Las cuatro columnas de
 * moneda que congeló se atan aquí una vez, en vez de repetirlas en cada fila.
 */
function Amount({ model, value }: { model: PurchaseReturn; value: string }) {
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

export default function PurchaseReturnsShow({ purchaseReturn }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing, errors, transform } = useForm({ status: '' });

    const statusUrl = purchaseReturns.updateStatus({
        company: companyId,
        id: purchaseReturn.id,
    }).url;

    /** El error de transición llega en `status`, que aquí no se captura. */
    const statusError = (errors as Record<string, string | undefined>).status;

    const transitions = STATUS_TRANSITIONS[purchaseReturn.status] ?? [];
    const activeLines = (purchaseReturn.lines ?? []).filter(
        (line) => line.status === 'active',
    );

    /** El estado destino viaja en el `transform`: la pantalla no captura nada. */
    const advanceTo = (status: PurchaseReturnStatus) => {
        transform(() => ({ status }));
        put(statusUrl);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Devoluciones de compras',
            href: purchaseReturns.index(companyId).url,
        },
        {
            title: purchaseReturn.code,
            href: purchaseReturns.show({
                company: companyId,
                id: purchaseReturn.id,
            }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={purchaseReturn.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={purchaseReturns.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Devoluciones de compras
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {purchaseReturn.code}
                            </h1>
                            <StatusPill
                                kind={STATUS_PILL_KIND[purchaseReturn.status]}
                            >
                                {STATUS_LABELS[purchaseReturn.status]}
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Truck className="size-3.5 opacity-80" />
                                {purchaseReturn.supplier_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Warehouse className="size-3.5 opacity-80" />
                                {purchaseReturn.warehouse_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Calendar className="size-3.5 opacity-80" />
                                {purchaseReturn.return_date}
                            </span>
                            {purchaseReturn.purchase_invoice_id && (
                                <Link
                                    href={
                                        purchaseInvoices.show({
                                            company: companyId,
                                            id: purchaseReturn.purchase_invoice_id,
                                        }).url
                                    }
                                    className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground transition-colors hover:text-primary"
                                >
                                    <ReceiptText className="size-3.5 opacity-80" />
                                    {purchaseReturn.purchase_invoice_code ??
                                        'Factura de compra'}
                                </Link>
                            )}
                            {purchaseReturn.credit_note_id && (
                                <Link
                                    href={
                                        purchaseCreditNotes.show({
                                            company: companyId,
                                            id: purchaseReturn.credit_note_id,
                                        }).url
                                    }
                                    className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground transition-colors hover:text-primary"
                                >
                                    <FileMinus className="size-3.5 opacity-80" />
                                    {purchaseReturn.credit_note_code ??
                                        'Nota de crédito'}
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
                        {isEditable(purchaseReturn.status) && (
                            <Link
                                href={
                                    purchaseReturns.edit({
                                        company: companyId,
                                        id: purchaseReturn.id,
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
                            value={REASON_LABELS[purchaseReturn.reason]}
                        />
                        <DataRow
                            label="Factura de origen"
                            value={purchaseReturn.purchase_invoice_code ?? '—'}
                        />
                        <DataRow
                            label="Nota de crédito"
                            value={purchaseReturn.credit_note_code ?? '—'}
                        />
                        {purchaseReturn.reason_detail && (
                            <DataRow
                                label="Detalle"
                                value={purchaseReturn.reason_detail}
                            />
                        )}
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Traslado
                        </div>
                        <DataRow
                            label="Bodega de salida"
                            value={purchaseReturn.warehouse_name ?? '—'}
                        />
                        <DataRow
                            label="Transportista"
                            value={purchaseReturn.carrier ?? '—'}
                        />
                        <DataRow
                            label="Guía de retorno"
                            value={purchaseReturn.tracking_number ?? '—'}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4 lg:col-span-2">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Importes
                        </div>
                        <DataRow
                            label="Moneda"
                            value={`${purchaseReturn.currency} (tasa ${purchaseReturn.exchange_rate})`}
                        />
                        <DataRow
                            label="Moneda de la empresa"
                            value={
                                purchaseReturn.base_currency
                                    ? `${purchaseReturn.base_currency} (tasa ${purchaseReturn.base_exchange_rate})`
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Subtotal"
                            value={
                                <Amount
                                    model={purchaseReturn}
                                    value={purchaseReturn.subtotal}
                                />
                            }
                        />
                        <DataRow
                            label="Impuesto"
                            value={
                                <Amount
                                    model={purchaseReturn}
                                    value={purchaseReturn.tax_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Total devuelto"
                            value={
                                <Amount
                                    model={purchaseReturn}
                                    value={purchaseReturn.total}
                                />
                            }
                        />
                    </Card>
                </div>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="border-b p-5 text-[13px] font-bold text-muted-foreground">
                        Líneas
                    </div>
                    <div className="hidden h-11 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.4fr_2.2fr_1.2fr_1fr_1fr_1fr]">
                        {[
                            '#',
                            'Artículo',
                            'Bodega',
                            'Cantidad',
                            'Costo',
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
                                        {line.reason
                                            ? ` · ${REASON_LABELS[line.reason]}`
                                            : ''}
                                    </span>
                                </div>
                                <div className="truncate text-[13px] text-muted-foreground">
                                    {line.warehouse_name ?? '—'}
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
                                La devolución no tiene líneas activas.
                            </div>
                        )}
                    </div>
                </Card>

                {purchaseReturn.cancelled_at && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Ban className="size-[15px]" />
                            Anulada
                        </div>
                        <p className="text-sm leading-relaxed">
                            El {purchaseReturn.cancelled_at}. El documento se
                            conserva y el kardex recibió su contrapartida: no se
                            elimina nada.
                        </p>
                    </Card>
                )}

                {purchaseReturn.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {purchaseReturn.notes}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
