import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    Ban,
    Calendar,
    ExternalLink,
    FileCheck,
    Mail,
    MapPin,
    Phone,
    StickyNote,
    UserRound,
} from 'lucide-react';
import { useState } from 'react';
import { AmountDual } from '@/components/amount-dual';
import type { AjaxOption } from '@/components/select2-ajax';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/lib/money';
import clients from '@/routes/clients';
import salesOrders from '@/routes/sales-orders';
import storeCustomers from '@/routes/store-customers';
import storeOrders from '@/routes/store-orders';
import type { BreadcrumbItem } from '@/types';
import { StoreOrderConvertDialog } from '../components/StoreOrderConvertDialog';
import { StoreOrderRejectDialog } from '../components/StoreOrderRejectDialog';
import { useStorePermissions } from '../hooks/useStorePermissions';
import {
    formatDocument,
    ORDER_STATUS_LABELS,
    ORDER_STATUS_PILL,
    type StoreOrder,
} from '../types/Store';

interface Props {
    store_order: StoreOrder;
    suggested_client: AjaxOption | null;
    customer_needs_document: boolean;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    errors?: Record<string, string>;
    [key: string]: unknown;
}

function DataRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-4 text-[13.5px]">
            <span className="font-medium text-muted-foreground">{label}</span>
            <b className="text-right font-bold">{value}</b>
        </div>
    );
}

export default function StoreOrdersShow({
    store_order: order,
    suggested_client,
    customer_needs_document,
}: Props) {
    const { currentCompany, errors } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const { can } = useStorePermissions();

    const [convertOpen, setConvertOpen] = useState(false);
    const [rejectOpen, setRejectOpen] = useState(false);
    const [converting, setConverting] = useState(false);

    const isPending = order.status === 'pending';
    const linked =
        order.store_customer?.client_id !== null &&
        order.store_customer?.client_id !== undefined;

    /**
     * Comprador ya vinculado: convierte directo, sin diálogo. Bodega, lista y
     * vendedor salen de los defaults; se editan en la orden `draft`.
     */
    const convert = () => {
        if (!linked) {
            setConvertOpen(true);

            return;
        }

        setConverting(true);
        router.put(
            storeOrders.convert({ company: companyId, id: order.id }).url,
            {},
            { preserveScroll: true, onFinish: () => setConverting(false) },
        );
    };

    const activeLines = (order.lines ?? []).filter(
        (line) => line.status === 'active',
    );

    const deliveryText = order.client_address
        ? `${order.client_address.name} · ${order.client_address.address}`
        : [order.delivery_address, order.delivery_city, order.delivery_state]
              .filter(Boolean)
              .join(', ') || '—';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pedidos web', href: storeOrders.index(companyId).url },
        {
            title: order.code,
            href: storeOrders.show({ company: companyId, id: order.id }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={order.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={storeOrders.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Pedidos web
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {order.code}
                            </h1>
                            <StatusPill kind={ORDER_STATUS_PILL[order.status]}>
                                {ORDER_STATUS_LABELS[order.status]}
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Calendar className="size-3.5 opacity-80" />
                                {order.created_at ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <UserRound className="size-3.5 opacity-80" />
                                {order.buyer_name}
                            </span>
                            {order.sales_order && (
                                <Link
                                    href={
                                        salesOrders.show({
                                            company: companyId,
                                            id: order.sales_order.id,
                                        }).url
                                    }
                                    className="inline-flex items-center gap-1.5 text-[13.5px] font-semibold text-primary hover:underline"
                                >
                                    <FileCheck className="size-3.5" />
                                    Orden {order.sales_order.code}
                                    <ExternalLink className="size-3" />
                                </Link>
                            )}
                        </div>
                    </div>
                    {isPending && (
                        <div className="flex flex-wrap gap-2.5">
                            {can('store-orders.convert') && (
                                <Button
                                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                                    onClick={convert}
                                    disabled={converting}
                                >
                                    <FileCheck />
                                    {converting
                                        ? 'Convirtiendo…'
                                        : 'Convertir en orden de venta'}
                                </Button>
                            )}
                            {can('store-orders.reject') && (
                                <Button
                                    variant="outline"
                                    className="h-10 rounded-[11px] bg-card px-4 font-semibold text-bad"
                                    onClick={() => setRejectOpen(true)}
                                >
                                    <Ban />
                                    Rechazar
                                </Button>
                            )}
                        </div>
                    )}
                </Card>

                {errors?.status && (
                    <p className="text-sm text-bad">{errors.status}</p>
                )}

                {order.needs_review && (
                    <div className="flex items-start gap-3 rounded-[12px] border border-warn bg-warn-soft p-4 text-[13.5px] text-warn">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                        <span>
                            El cliente vinculado está inactivo o tiene el
                            crédito bloqueado. El pedido se puede convertir,
                            pero la orden no podrá confirmarse hasta resolverlo.
                        </span>
                    </div>
                )}

                {order.status === 'rejected' && (
                    <Card className="gap-2 rounded-2xl border-bad/40 px-[18px] py-4">
                        <div className="text-[13px] font-bold text-bad">
                            Rechazado{' '}
                            {order.rejected_at ? `el ${order.rejected_at}` : ''}
                        </div>
                        <p className="text-sm leading-relaxed">
                            {order.rejection_reason}
                        </p>
                    </Card>
                )}

                <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Comprador
                        </div>
                        <DataRow
                            label="Cuenta"
                            value={
                                order.store_customer ? (
                                    <Link
                                        href={
                                            storeCustomers.show({
                                                company: companyId,
                                                id: order.store_customer.id,
                                            }).url
                                        }
                                        className="inline-flex items-center gap-1 text-primary hover:underline"
                                    >
                                        {order.store_customer.code} —{' '}
                                        {order.store_customer.name}
                                        <ExternalLink className="size-3.5" />
                                    </Link>
                                ) : (
                                    '—'
                                )
                            }
                        />
                        <DataRow
                            label="Nombre en el pedido"
                            value={order.buyer_name}
                        />
                        <DataRow
                            label="RIF"
                            value={formatDocument(
                                order.buyer_document_type,
                                order.buyer_document_number,
                            )}
                        />
                        <DataRow
                            label="Correo"
                            value={
                                <span className="inline-flex items-center gap-1.5">
                                    <Mail className="size-3.5 opacity-70" />
                                    {order.buyer_email}
                                </span>
                            }
                        />
                        <DataRow
                            label="Teléfono"
                            value={
                                <span className="inline-flex items-center gap-1.5">
                                    <Phone className="size-3.5 opacity-70" />
                                    {order.buyer_phone}
                                </span>
                            }
                        />
                        <DataRow
                            label="Cliente"
                            value={
                                order.client ? (
                                    <Link
                                        href={
                                            clients.show({
                                                company: companyId,
                                                id: order.client.id,
                                            }).url
                                        }
                                        className="inline-flex items-center gap-1 text-primary hover:underline"
                                    >
                                        {order.client.code} —{' '}
                                        {order.client.name}
                                        <ExternalLink className="size-3.5" />
                                    </Link>
                                ) : (
                                    'Sin cliente todavía'
                                )
                            }
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Entrega y valor
                        </div>
                        <DataRow
                            label="Dirección"
                            value={
                                <span className="inline-flex items-center gap-1.5 text-right">
                                    <MapPin className="size-3.5 shrink-0 opacity-70" />
                                    {deliveryText}
                                </span>
                            }
                        />
                        <DataRow
                            label="Moneda"
                            value={`${order.currency} · tasa ${order.exchange_rate}`}
                        />
                        <DataRow
                            label="Subtotal"
                            value={formatMoney(order.subtotal, order.currency)}
                        />
                        <DataRow
                            label="Total"
                            value={
                                <AmountDual
                                    amount={order.total}
                                    currency={order.currency}
                                    rate={order.exchange_rate}
                                />
                            }
                        />
                        {order.status === 'converted' && (
                            <DataRow
                                label="Convertido por"
                                value={`${order.converter?.name ?? '—'} · ${order.converted_at ?? ''}`}
                            />
                        )}
                    </Card>
                </div>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="border-b p-5 text-[13px] font-bold text-muted-foreground">
                        Líneas
                    </div>
                    <div className="hidden h-11 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.4fr_2.6fr_0.9fr_1fr_1fr]">
                        {[
                            '#',
                            'Artículo',
                            'Cantidad',
                            'Precio',
                            'Subtotal',
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
                                className="grid gap-3.5 border-b px-5 py-3 last:border-b-0 lg:grid-cols-[0.4fr_2.6fr_0.9fr_1fr_1fr] lg:items-center"
                            >
                                <div className="text-[13.5px] font-semibold text-muted-foreground tabular-nums">
                                    {line.line_number}
                                </div>
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {line.store_item?.title ??
                                            line.item?.name ??
                                            '—'}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {line.item?.code ?? ''}
                                        {line.item?.sku
                                            ? ` · ${line.item.sku}`
                                            : ''}
                                        {line.measurement_unit
                                            ? ` · ${line.measurement_unit.name}`
                                            : ''}
                                    </span>
                                </div>
                                <div className="text-[13.5px] tabular-nums">
                                    {line.quantity}
                                </div>
                                <div className="text-[13.5px] tabular-nums">
                                    {formatMoney(
                                        line.unit_price,
                                        order.currency,
                                    )}
                                </div>
                                <div className="text-[13.5px] font-semibold tabular-nums">
                                    {formatMoney(line.subtotal, order.currency)}
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

                {order.buyer_notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Comentario del comprador
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {order.buyer_notes}
                        </p>
                    </Card>
                )}

                {order.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas internas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {order.notes}
                        </p>
                    </Card>
                )}
            </div>

            {isPending && (
                <>
                    <StoreOrderConvertDialog
                        order={order}
                        open={convertOpen}
                        onClose={() => setConvertOpen(false)}
                        suggestedClient={suggested_client}
                        customerNeedsDocument={customer_needs_document}
                    />
                    <StoreOrderRejectDialog
                        order={order}
                        open={rejectOpen}
                        onClose={() => setRejectOpen(false)}
                    />
                </>
            )}
        </AppLayout>
    );
}
