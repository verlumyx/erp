import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Calendar,
    Check,
    ClipboardList,
    Edit,
    Hash,
    StickyNote,
    Truck,
    Warehouse,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { AmountDual } from '@/components/amount-dual';
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
import purchaseInvoices from '@/routes/purchase-invoices';
import purchaseOrders from '@/routes/purchase-orders';
import type { BreadcrumbItem } from '@/types';
import {
    isEditable,
    PAYMENT_STATUS_LABELS,
    PAYMENT_STATUS_PILL_KIND,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    STATUS_TRANSITIONS,
    type PurchaseInvoice,
    type PurchaseInvoiceStatus,
} from './types/PurchaseInvoice';

interface Props {
    purchaseInvoice: PurchaseInvoice;
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
function Amount({
    invoice,
    value,
}: {
    invoice: PurchaseInvoice;
    value: string;
}) {
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

export default function PurchaseInvoicesShow({ purchaseInvoice }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [cancelOpen, setCancelOpen] = useState(false);

    const { data, setData, put, processing, errors, reset, transform } =
        useForm({ cancellation_reason: '' });

    const statusUrl = purchaseInvoices.updateStatus({
        company: companyId,
        id: purchaseInvoice.id,
    }).url;

    /** El error de transición llega en `status`, que no es campo del formulario. */
    const statusError = (errors as Record<string, string | undefined>).status;

    const transitions = STATUS_TRANSITIONS[purchaseInvoice.status] ?? [];
    const activeLines = (purchaseInvoice.lines ?? []).filter(
        (line) => line.status === 'active',
    );

    /** El estado destino viaja en el `transform`: el formulario solo captura el motivo. */
    const advanceTo = (status: PurchaseInvoiceStatus) => {
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
        {
            title: 'Facturas de compra',
            href: purchaseInvoices.index(companyId).url,
        },
        {
            title: purchaseInvoice.code,
            href: purchaseInvoices.show({
                company: companyId,
                id: purchaseInvoice.id,
            }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={purchaseInvoice.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={purchaseInvoices.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Facturas de compra
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {purchaseInvoice.code}
                            </h1>
                            <StatusPill
                                kind={STATUS_PILL_KIND[purchaseInvoice.status]}
                            >
                                {STATUS_LABELS[purchaseInvoice.status]}
                            </StatusPill>
                            <StatusPill
                                kind={
                                    PAYMENT_STATUS_PILL_KIND[
                                        purchaseInvoice.payment_status
                                    ]
                                }
                            >
                                {
                                    PAYMENT_STATUS_LABELS[
                                        purchaseInvoice.payment_status
                                    ]
                                }
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Truck className="size-3.5 opacity-80" />
                                {purchaseInvoice.supplier_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Hash className="size-3.5 opacity-80" />
                                {purchaseInvoice.supplier_invoice_number}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Warehouse className="size-3.5 opacity-80" />
                                {purchaseInvoice.warehouse_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Calendar className="size-3.5 opacity-80" />
                                {purchaseInvoice.invoice_date}
                            </span>
                            {purchaseInvoice.sourceable_id && (
                                <Link
                                    href={
                                        purchaseOrders.show({
                                            company: companyId,
                                            id: purchaseInvoice.sourceable_id,
                                        }).url
                                    }
                                    className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground transition-colors hover:text-primary"
                                >
                                    <ClipboardList className="size-3.5 opacity-80" />
                                    {purchaseInvoice.sourceable_code ??
                                        'Orden de compra'}
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
                                onClick={() => setCancelOpen(true)}
                                disabled={processing}
                            >
                                <Ban />
                                Anular
                            </Button>
                        )}
                        {isEditable(purchaseInvoice.status) && (
                            <Link
                                href={
                                    purchaseInvoices.edit({
                                        company: companyId,
                                        id: purchaseInvoice.id,
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
                            label="Serie fiscal"
                            value={
                                purchaseInvoice.supplier_invoice_series ?? '—'
                            }
                        />
                        <DataRow
                            label="Fecha de emisión"
                            value={purchaseInvoice.invoice_date}
                        />
                        <DataRow
                            label="Fecha de recepción"
                            value={purchaseInvoice.received_date ?? '—'}
                        />
                        <DataRow
                            label="Vencimiento"
                            value={purchaseInvoice.due_date}
                        />
                        <DataRow
                            label="Afecta inventario"
                            value={
                                purchaseInvoice.affects_inventory === 'yes'
                                    ? 'Sí'
                                    : 'No, ya entró antes'
                            }
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Importes
                        </div>
                        <DataRow
                            label="Moneda"
                            value={`${purchaseInvoice.currency} (tasa ${purchaseInvoice.exchange_rate})`}
                        />
                        <DataRow
                            label="Moneda de la empresa"
                            value={
                                purchaseInvoice.base_currency
                                    ? `${purchaseInvoice.base_currency} (tasa ${purchaseInvoice.base_exchange_rate})`
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Subtotal"
                            value={
                                <Amount
                                    invoice={purchaseInvoice}
                                    value={purchaseInvoice.subtotal}
                                />
                            }
                        />
                        <DataRow
                            label="Descuento global"
                            value={
                                <Amount
                                    invoice={purchaseInvoice}
                                    value={purchaseInvoice.discount_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Impuesto"
                            value={
                                <Amount
                                    invoice={purchaseInvoice}
                                    value={purchaseInvoice.tax_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Flete"
                            value={
                                <Amount
                                    invoice={purchaseInvoice}
                                    value={purchaseInvoice.freight_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Otros gastos"
                            value={
                                <Amount
                                    invoice={purchaseInvoice}
                                    value={purchaseInvoice.other_charges}
                                />
                            }
                        />
                        <DataRow
                            label="Total"
                            value={
                                <Amount
                                    invoice={purchaseInvoice}
                                    value={purchaseInvoice.total}
                                />
                            }
                        />
                        <DataRow
                            label="Retención"
                            value={
                                <Amount
                                    invoice={purchaseInvoice}
                                    value={purchaseInvoice.withholding_amount}
                                />
                            }
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Cuenta por pagar
                        </div>
                        <DataRow
                            label="Pagado"
                            value={
                                <Amount
                                    invoice={purchaseInvoice}
                                    value={purchaseInvoice.paid_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Saldo"
                            value={
                                <Amount
                                    invoice={purchaseInvoice}
                                    value={purchaseInvoice.balance}
                                />
                            }
                        />
                        <DataRow
                            label="Estado de pago"
                            value={
                                PAYMENT_STATUS_LABELS[
                                    purchaseInvoice.payment_status
                                ]
                            }
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Importes en bolívares
                        </div>
                        <p className="text-[12.5px] text-muted-foreground">
                            Congelados al emitir: la factura tiene valor legal y
                            su deuda en bolívares no se recalcula.
                        </p>
                        <DataRow
                            label="Subtotal"
                            value={purchaseInvoice.subtotal_ves}
                        />
                        <DataRow
                            label="Impuesto"
                            value={purchaseInvoice.tax_amount_ves}
                        />
                        <DataRow
                            label="Total"
                            value={purchaseInvoice.total_ves}
                        />
                    </Card>
                </div>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="border-b p-5 text-[13px] font-bold text-muted-foreground">
                        Líneas
                    </div>
                    <div className="hidden h-11 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.4fr_2.4fr_1fr_1fr_1fr_1fr]">
                        {[
                            '#',
                            'Artículo',
                            'Cantidad',
                            'Costo',
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
                                className="grid gap-3.5 border-b px-5 py-3 last:border-b-0 lg:grid-cols-[0.4fr_2.4fr_1fr_1fr_1fr_1fr] lg:items-center"
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
                                La factura no tiene líneas activas.
                            </div>
                        )}
                    </div>
                </Card>

                {purchaseInvoice.cancellation_reason && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Ban className="size-[15px]" />
                            Motivo de anulación
                        </div>
                        <p className="text-sm leading-relaxed">
                            {purchaseInvoice.cancellation_reason}
                        </p>
                    </Card>
                )}

                {purchaseInvoice.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {purchaseInvoice.notes}
                        </p>
                    </Card>
                )}
            </div>

            <Dialog open={cancelOpen} onOpenChange={setCancelOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Anular factura de compra</DialogTitle>
                        <DialogDescription>
                            La factura no se elimina: queda anulada con el
                            motivo que indiques.
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
                            Anular factura
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
