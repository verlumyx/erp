import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    CalendarClock,
    Check,
    ClipboardList,
    Edit,
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
import salesOrders from '@/routes/sales-orders';
import type { BreadcrumbItem } from '@/types';
import { SalesOrderStatusPill } from './components/SalesOrderStatusPill';
import {
    formatAmount,
    isEditable,
    STATUS_LABELS,
    STATUS_TRANSITIONS,
    type SalesOrder,
    type SalesOrderStatus,
} from './types/SalesOrder';

interface Props {
    salesOrder: SalesOrder;
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
 * Un importe del pedido con su equivalente debajo. Las cuatro columnas de
 * moneda que congeló el pedido se atan aquí una vez, en vez de repetirlas en
 * cada fila.
 */
function Amount({ order, value }: { order: SalesOrder; value: string }) {
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

export default function SalesOrdersShow({ salesOrder: order }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [showCancelForm, setShowCancelForm] = useState(false);

    const { data, setData, put, transform, processing, errors } = useForm<{
        status: SalesOrderStatus;
        cancellation_reason: string;
    }>({
        status: order.status,
        cancellation_reason: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Órdenes de venta', href: salesOrders.index(companyId).url },
        {
            title: order.code,
            href: salesOrders.show({ company: companyId, id: order.id }).url,
        },
    ];

    const activeLines = (order.lines ?? []).filter(
        (line) => line.status === 'active',
    );

    const allowed = STATUS_TRANSITIONS[order.status];
    const canConfirm = allowed.includes('confirmed');
    const canCancel = allowed.includes('cancelled');

    /**
     * `transform` arma el payload justo antes de enviar: `setData` es asíncrono
     * y mandaría el estado anterior.
     */
    const submitStatus = (status: SalesOrderStatus, reason = '') => {
        transform(() => ({ status, cancellation_reason: reason }));

        put(salesOrders.updateStatus({ company: companyId, id: order.id }).url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={order.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={salesOrders.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Órdenes de venta
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {order.client_name ?? 'Pedido'}
                            </h1>
                            <SalesOrderStatusPill status={order.status} />
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Hash className="size-3.5 opacity-80" />
                                {order.code}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <CalendarClock className="size-3.5 opacity-80" />
                                {order.order_date}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Warehouse className="size-3.5 opacity-80" />
                                {order.warehouse_name ?? '—'}
                            </span>
                            {order.salesperson_name && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <User className="size-3.5 opacity-80" />
                                    {order.salesperson_name}
                                </span>
                            )}
                            {order.client_reference && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <ClipboardList className="size-3.5 opacity-80" />
                                    {order.client_reference}
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
                                Confirmar
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
                        {isEditable(order) && (
                            <Link
                                href={
                                    salesOrders.edit({
                                        company: companyId,
                                        id: order.id,
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
                            Anular el pedido
                        </div>
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
                            Pedido
                        </div>
                        <DataRow
                            label="Cliente"
                            value={`${order.client_code ? `${order.client_code} · ` : ''}${order.client_name ?? '—'}`}
                        />
                        <DataRow
                            label="Dirección de entrega"
                            value={
                                order.client_address_name ??
                                'Dirección fiscal del cliente'
                            }
                        />
                        <DataRow
                            label="Fecha comprometida"
                            value={order.expected_date ?? '—'}
                        />
                        <DataRow
                            label="Lista de precio"
                            value={
                                order.price_list_name ??
                                'Lista por defecto de la empresa'
                            }
                        />
                        <DataRow
                            label="Días de crédito"
                            value={
                                order.payment_term_days === 0
                                    ? 'Contado'
                                    : String(order.payment_term_days)
                            }
                        />
                        <DataRow
                            label="Estado"
                            value={STATUS_LABELS[order.status]}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Importes ({order.currency})
                        </div>
                        <DataRow
                            label="Subtotal"
                            value={
                                <Amount order={order} value={order.subtotal} />
                            }
                        />
                        <DataRow
                            label="Descuento"
                            value={
                                <Amount
                                    order={order}
                                    value={order.discount_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Impuesto"
                            value={
                                <Amount
                                    order={order}
                                    value={order.tax_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Total"
                            value={<Amount order={order} value={order.total} />}
                        />
                        <DataRow
                            label="Tasa de cambio"
                            value={order.exchange_rate}
                        />
                        <DataRow
                            label="Moneda de la empresa"
                            value={
                                order.base_currency
                                    ? `${order.base_currency} (tasa ${order.base_exchange_rate})`
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Despachado"
                            value={`${formatAmount(order.dispatched_percent)}%`}
                        />
                        <DataRow
                            label="Facturado"
                            value={`${formatAmount(order.invoiced_percent)}%`}
                        />
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
                                El pedido no tiene líneas activas.
                            </div>
                        )}
                    </div>
                </Card>

                {order.cancellation_reason && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Ban className="size-[15px]" />
                            Motivo de la anulación
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {order.cancellation_reason}
                        </p>
                    </Card>
                )}

                {order.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Nota
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {order.notes}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
