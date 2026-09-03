import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    CalendarClock,
    Check,
    CircleDollarSign,
    Edit,
    FileText,
    Hash,
    Package,
    StickyNote,
    User,
    Warehouse,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { AmountDual } from '@/components/amount-dual';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import salesInvoices from '@/routes/sales-invoices';
import salesOrders from '@/routes/sales-orders';
import type { BreadcrumbItem } from '@/types';
import {
    SalesInvoicePaymentPill,
    SalesInvoiceStatusPill,
} from './components/SalesInvoiceStatusPill';
import {
    formatAmount,
    isEditable,
    SALE_TYPE_LABELS,
    STATUS_LABELS,
    STATUS_TRANSITIONS,
    type SalesInvoice,
    type SalesInvoiceStatus,
} from './types/SalesInvoice';

interface Props {
    salesInvoice: SalesInvoice;
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
 * Un importe de la factura con su equivalente debajo. Las cuatro columnas de
 * moneda que congeló la factura se atan aquí una vez, en vez de repetirlas en
 * cada fila.
 */
function Amount({ invoice, value }: { invoice: SalesInvoice; value: string }) {
    return (
        <AmountDual
            amount={value}
            currency={invoice.currency}
            rate={invoice.exchange_rate}
            baseCurrency={invoice.base_currency}
            baseRate={invoice.base_exchange_rate}
            className="items-end"
        />
    );
}

export default function SalesInvoicesShow({ salesInvoice: invoice }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [showCancelForm, setShowCancelForm] = useState(false);

    const { data, setData, put, transform, processing, errors } = useForm<{
        status: SalesInvoiceStatus;
        cancellation_reason: string;
    }>({
        status: invoice.status,
        cancellation_reason: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Facturas de venta',
            href: salesInvoices.index(companyId).url,
        },
        {
            title: invoice.code,
            href: salesInvoices.show({ company: companyId, id: invoice.id })
                .url,
        },
    ];

    const activeLines = (invoice.lines ?? []).filter(
        (line) => line.status === 'active',
    );

    const allowed = STATUS_TRANSITIONS[invoice.status];
    const canConfirm = allowed.includes('confirmed');
    const canComplete = allowed.includes('completed');
    const canCancel = allowed.includes('cancelled');

    /** El número fiscal completo, con su serie cuando la lleva. */
    const fiscalNumber = invoice.invoice_number
        ? `${invoice.invoice_series ? `${invoice.invoice_series}-` : ''}${invoice.invoice_number}`
        : 'Se asigna al emitirla';

    /**
     * `transform` arma el payload justo antes de enviar: `setData` es asíncrono
     * y mandaría el estado anterior.
     */
    const submitStatus = (status: SalesInvoiceStatus, reason = '') => {
        transform(() => ({ status, cancellation_reason: reason }));

        put(
            salesInvoices.updateStatus({ company: companyId, id: invoice.id })
                .url,
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={invoice.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={salesInvoices.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Facturas de venta
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {invoice.client_name ?? 'Factura'}
                            </h1>
                            <SalesInvoiceStatusPill status={invoice.status} />
                            <SalesInvoicePaymentPill
                                status={invoice.payment_status}
                            />
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Hash className="size-3.5 opacity-80" />
                                {invoice.code}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <FileText className="size-3.5 opacity-80" />
                                {fiscalNumber}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <CalendarClock className="size-3.5 opacity-80" />
                                {invoice.invoice_date} · vence{' '}
                                {invoice.due_date}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Warehouse className="size-3.5 opacity-80" />
                                {invoice.warehouse_name ?? '—'}
                            </span>
                            {invoice.salesperson_name && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <User className="size-3.5 opacity-80" />
                                    {invoice.salesperson_name}
                                </span>
                            )}
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        {canConfirm && (
                            <Button
                                className="h-10 rounded-[11px] px-4 font-semibold"
                                onClick={() => submitStatus('confirmed')}
                                disabled={processing}
                            >
                                <Check />
                                Emitir
                            </Button>
                        )}
                        {canComplete && (
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                onClick={() => submitStatus('completed')}
                                disabled={processing}
                            >
                                <CircleDollarSign />
                                Completar
                            </Button>
                        )}
                        {canCancel && (
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                onClick={() => setShowCancelForm((v) => !v)}
                                disabled={processing}
                            >
                                <Ban />
                                Anular
                            </Button>
                        )}
                        {isEditable(invoice) && (
                            <Link
                                href={
                                    salesInvoices.edit({
                                        company: companyId,
                                        id: invoice.id,
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

                {errors.status && (
                    <Card className="rounded-2xl border-bad px-[18px] py-4">
                        <p className="text-sm font-semibold text-bad">
                            {errors.status}
                        </p>
                    </Card>
                )}

                {showCancelForm && canCancel && (
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Anular la factura
                        </div>
                        <p className="text-[13px] text-muted-foreground">
                            Anular exige que no tenga cobros aplicados y libera
                            la cuenta por cobrar del cliente.
                        </p>
                        <Textarea
                            value={data.cancellation_reason}
                            onChange={(e) =>
                                setData('cancellation_reason', e.target.value)
                            }
                            placeholder="Motivo de la anulación"
                            className="rounded-[10px]"
                            rows={2}
                            maxLength={500}
                        />
                        {errors.cancellation_reason && (
                            <p className="text-sm text-bad">
                                {errors.cancellation_reason}
                            </p>
                        )}
                        <div className="flex gap-2.5">
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                onClick={() =>
                                    submitStatus(
                                        'cancelled',
                                        data.cancellation_reason,
                                    )
                                }
                                disabled={processing}
                            >
                                <Ban />
                                Confirmar anulación
                            </Button>
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                onClick={() => setShowCancelForm(false)}
                                disabled={processing}
                            >
                                Volver
                            </Button>
                        </div>
                    </Card>
                )}

                <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Factura
                        </div>
                        <DataRow
                            label="Cliente"
                            value={`${invoice.client_code ? `${invoice.client_code} · ` : ''}${invoice.client_name ?? '—'}`}
                        />
                        <DataRow
                            label="Dirección de entrega"
                            value={
                                invoice.client_address_name ??
                                'Dirección fiscal del cliente'
                            }
                        />
                        <DataRow
                            label="Documento origen"
                            value={
                                invoice.sourceable_id ? (
                                    <Link
                                        href={
                                            salesOrders.show({
                                                company: companyId,
                                                id: invoice.sourceable_id,
                                            }).url
                                        }
                                        className="text-primary hover:underline"
                                    >
                                        {invoice.sourceable_code ??
                                            'Pedido de venta'}
                                    </Link>
                                ) : (
                                    'Factura directa'
                                )
                            }
                        />
                        <DataRow
                            label="Forma de venta"
                            value={SALE_TYPE_LABELS[invoice.sale_type]}
                        />
                        <DataRow
                            label="Estado"
                            value={STATUS_LABELS[invoice.status]}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Importes ({invoice.currency})
                        </div>
                        <DataRow
                            label="Subtotal"
                            value={
                                <Amount
                                    invoice={invoice}
                                    value={invoice.subtotal}
                                />
                            }
                        />
                        <DataRow
                            label="Descuento"
                            value={
                                <Amount
                                    invoice={invoice}
                                    value={invoice.discount_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Impuesto"
                            value={
                                <Amount
                                    invoice={invoice}
                                    value={invoice.tax_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Total"
                            value={
                                <Amount
                                    invoice={invoice}
                                    value={invoice.total}
                                />
                            }
                        />
                        <DataRow
                            label="Retención del cliente"
                            value={
                                <Amount
                                    invoice={invoice}
                                    value={invoice.withholding_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Cobrado"
                            value={
                                <Amount
                                    invoice={invoice}
                                    value={invoice.paid_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Saldo"
                            value={
                                <Amount
                                    invoice={invoice}
                                    value={invoice.balance}
                                />
                            }
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Moneda y valor legal
                        </div>
                        <DataRow
                            label="Tasa de cambio"
                            value={invoice.exchange_rate}
                        />
                        <DataRow
                            label="Moneda de la empresa"
                            value={
                                invoice.base_currency
                                    ? `${invoice.base_currency} (tasa ${invoice.base_exchange_rate})`
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Subtotal en Bs."
                            value={formatAmount(invoice.subtotal_ves)}
                        />
                        <DataRow
                            label="Impuesto en Bs."
                            value={formatAmount(invoice.tax_amount_ves)}
                        />
                        <DataRow
                            label="Total en Bs."
                            value={formatAmount(invoice.total_ves)}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Margen
                        </div>
                        <DataRow
                            label="Costo de lo vendido"
                            value={
                                <Amount
                                    invoice={invoice}
                                    value={invoice.total_cost}
                                />
                            }
                        />
                        <DataRow
                            label="Margen"
                            value={
                                <Amount
                                    invoice={invoice}
                                    value={String(
                                        Number(invoice.subtotal) -
                                            Number(invoice.total_cost),
                                    )}
                                />
                            }
                        />
                        <p className="text-[12.5px] text-muted-foreground">
                            El costo se congela al emitir la factura: en
                            borrador todavía vale cero.
                        </p>
                    </Card>
                </div>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="flex items-center gap-2 border-b p-5 text-[13px] font-bold text-muted-foreground">
                        <Package className="size-[15px]" />
                        Líneas
                    </div>
                    <div className="hidden h-11 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.4fr_2.4fr_1fr_1fr_1fr_1fr_1fr]">
                        {[
                            '#',
                            'Artículo',
                            'Cantidad',
                            'Precio',
                            'Desc.',
                            'Impuesto',
                            'Total',
                        ].map((header, index) => (
                            <div
                                key={header}
                                className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                    index >= 2 ? 'text-right' : ''
                                }`}
                            >
                                {header}
                            </div>
                        ))}
                    </div>
                    <div className="flex flex-col">
                        {activeLines.map((line) => (
                            <div
                                key={line.id}
                                className="grid gap-3.5 border-b px-5 py-3 last:border-b-0 lg:grid-cols-[0.4fr_2.4fr_1fr_1fr_1fr_1fr_1fr] lg:items-center"
                            >
                                <div className="text-[13.5px] font-semibold text-muted-foreground tabular-nums">
                                    {line.line_number}
                                </div>
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {line.item_name ?? '—'}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {line.item_sku ?? ''}
                                        {line.measurement_unit_name
                                            ? ` · ${line.measurement_unit_name}`
                                            : ''}
                                    </span>
                                </div>
                                <div className="text-[13.5px] tabular-nums lg:text-right">
                                    {formatAmount(line.quantity)}
                                </div>
                                <div className="text-[13.5px] tabular-nums lg:text-right">
                                    {formatAmount(line.unit_price)}
                                </div>
                                <div className="text-[13.5px] tabular-nums lg:text-right">
                                    {formatAmount(line.discount_amount)}
                                </div>
                                <div className="text-[13.5px] tabular-nums lg:text-right">
                                    {formatAmount(line.tax_amount)}
                                </div>
                                <div className="text-[13.5px] font-bold tabular-nums lg:text-right">
                                    {formatAmount(line.total)}
                                </div>
                            </div>
                        ))}
                        {activeLines.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                La factura no tiene líneas activas.
                            </div>
                        )}
                    </div>
                </Card>

                {invoice.cancellation_reason && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Ban className="size-[15px]" />
                            Motivo de la anulación
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {invoice.cancellation_reason}
                        </p>
                    </Card>
                )}

                {invoice.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Nota
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {invoice.notes}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
