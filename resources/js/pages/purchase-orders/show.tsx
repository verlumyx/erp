import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Calendar,
    Check,
    Edit,
    Hash,
    PackageCheck,
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
import {
    STATUS_LABELS as ENTRY_STATUS_LABELS,
    STATUS_PILL_KIND as ENTRY_STATUS_PILL_KIND,
} from '@/pages/entries/types/Entry';
import entries from '@/routes/entries';
import purchaseOrders from '@/routes/purchase-orders';
import type { BreadcrumbItem } from '@/types';
import {
    isEditable,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    STATUS_TRANSITIONS,
    type PurchaseOrder,
    type PurchaseOrderStatus,
} from './types/PurchaseOrder';

interface Props {
    purchaseOrder: PurchaseOrder;
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
 * Un importe de la orden con su equivalente debajo. Las cuatro columnas de
 * moneda que congeló la orden se atan aquí una vez, en vez de repetirlas en
 * cada fila.
 */
function Amount({ order, value }: { order: PurchaseOrder; value: string }) {
    return (
        <AmountDual
            amount={value}
            currency={order.currency}
            rate={order.exchange_rate}
            baseCurrency={order.base_currency}
            baseRate={order.base_exchange_rate}
            className="items-end"
        />
    );
}

export default function PurchaseOrdersShow({ purchaseOrder }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [cancelOpen, setCancelOpen] = useState(false);

    const { data, setData, put, processing, errors, reset, transform } =
        useForm({ cancellation_reason: '' });

    const statusUrl = purchaseOrders.updateStatus({
        company: companyId,
        id: purchaseOrder.id,
    }).url;

    /** El error de transición llega en `status`, que no es campo del formulario. */
    const statusError = (errors as Record<string, string | undefined>).status;

    const transitions = STATUS_TRANSITIONS[purchaseOrder.status] ?? [];
    const activeLines = (purchaseOrder.lines ?? []).filter(
        (line) => line.status === 'active',
    );

    /** El estado destino viaja en el `transform`: el formulario solo captura el motivo. */
    const advanceTo = (status: PurchaseOrderStatus) => {
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
            title: 'Órdenes de compra',
            href: purchaseOrders.index(companyId).url,
        },
        {
            title: purchaseOrder.code,
            href: purchaseOrders.show({
                company: companyId,
                id: purchaseOrder.id,
            }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={purchaseOrder.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={purchaseOrders.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Órdenes de compra
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {purchaseOrder.code}
                            </h1>
                            <StatusPill
                                kind={STATUS_PILL_KIND[purchaseOrder.status]}
                            >
                                {STATUS_LABELS[purchaseOrder.status]}
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Truck className="size-3.5 opacity-80" />
                                {purchaseOrder.supplier_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Warehouse className="size-3.5 opacity-80" />
                                {purchaseOrder.warehouse_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Calendar className="size-3.5 opacity-80" />
                                {purchaseOrder.order_date}
                            </span>
                            {purchaseOrder.supplier_reference && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {purchaseOrder.supplier_reference}
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
                                onClick={() => setCancelOpen(true)}
                                disabled={processing}
                            >
                                <Ban />
                                Anular
                            </Button>
                        )}
                        {isEditable(purchaseOrder.status) && (
                            <Link
                                href={
                                    purchaseOrders.edit({
                                        company: companyId,
                                        id: purchaseOrder.id,
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
                            Entrega
                        </div>
                        <DataRow
                            label="Fecha de emisión"
                            value={purchaseOrder.order_date}
                        />
                        <DataRow
                            label="Fecha estimada"
                            value={purchaseOrder.expected_date ?? '—'}
                        />
                        <DataRow
                            label="Recibido"
                            value={`${purchaseOrder.received_percent} %`}
                        />
                        <DataRow
                            label="Facturado"
                            value={`${purchaseOrder.invoiced_percent} %`}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Importes
                        </div>
                        <DataRow
                            label="Moneda"
                            value={`${purchaseOrder.currency} (tasa ${purchaseOrder.exchange_rate})`}
                        />
                        <DataRow
                            label="Moneda de la empresa"
                            value={
                                purchaseOrder.base_currency
                                    ? `${purchaseOrder.base_currency} (tasa ${purchaseOrder.base_exchange_rate})`
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Subtotal"
                            value={
                                <Amount
                                    order={purchaseOrder}
                                    value={purchaseOrder.subtotal}
                                />
                            }
                        />
                        <DataRow
                            label="Descuento global"
                            value={
                                <Amount
                                    order={purchaseOrder}
                                    value={purchaseOrder.discount_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Impuesto"
                            value={
                                <Amount
                                    order={purchaseOrder}
                                    value={purchaseOrder.tax_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Total"
                            value={
                                <Amount
                                    order={purchaseOrder}
                                    value={purchaseOrder.total}
                                />
                            }
                        />
                        <DataRow
                            label="Días de crédito"
                            value={
                                purchaseOrder.payment_term_days === 0
                                    ? 'Contado'
                                    : String(purchaseOrder.payment_term_days)
                            }
                        />
                    </Card>
                </div>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="border-b p-5 text-[13px] font-bold text-muted-foreground">
                        Líneas
                    </div>
                    <div className="hidden h-11 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.4fr_2.2fr_0.9fr_1fr_1fr_0.9fr_1fr]">
                        {[
                            '#',
                            'Artículo',
                            'Cantidad',
                            'Por recibir',
                            'Por facturar',
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
                                className="grid gap-3.5 border-b px-5 py-3 last:border-b-0 lg:grid-cols-[0.4fr_2.2fr_0.9fr_1fr_1fr_0.9fr_1fr] lg:items-center"
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
                                    {line.pending_quantity}
                                </div>
                                <div className="text-[13.5px] tabular-nums">
                                    {line.pending_invoiced_quantity}
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
                                La orden no tiene líneas activas.
                            </div>
                        )}
                    </div>
                </Card>

                {(purchaseOrder.entries?.length ?? 0) > 0 && (
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <PackageCheck className="size-[15px]" />
                            Entradas de mercancía
                        </div>
                        <div className="flex flex-col gap-2">
                            {purchaseOrder.entries?.map((entry) => (
                                <Link
                                    key={entry.id}
                                    href={
                                        entries.show({
                                            company: companyId,
                                            id: entry.id,
                                        }).url
                                    }
                                    className="flex items-center justify-between gap-3 rounded-xl border px-3 py-2 text-sm hover:bg-accent"
                                >
                                    <span className="font-medium">
                                        {entry.code}
                                    </span>
                                    <StatusPill
                                        kind={
                                            ENTRY_STATUS_PILL_KIND[entry.status]
                                        }
                                    >
                                        {ENTRY_STATUS_LABELS[entry.status]}
                                    </StatusPill>
                                </Link>
                            ))}
                        </div>
                    </Card>
                )}

                {purchaseOrder.cancellation_reason && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Ban className="size-[15px]" />
                            Motivo de anulación
                        </div>
                        <p className="text-sm leading-relaxed">
                            {purchaseOrder.cancellation_reason}
                        </p>
                    </Card>
                )}

                {purchaseOrder.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {purchaseOrder.notes}
                        </p>
                    </Card>
                )}
            </div>

            <Dialog open={cancelOpen} onOpenChange={setCancelOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Anular orden de compra</DialogTitle>
                        <DialogDescription>
                            La orden no se elimina: queda anulada con el motivo
                            que indiques.
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
                            Anular orden
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
