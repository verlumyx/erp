import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Clock,
    ExternalLink,
    Hash,
    Layers,
    MapPin,
    Package,
    Power,
    ShoppingCart,
    Truck,
    Warehouse as WarehouseIcon,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import itemLots from '@/routes/item-lots';
import itemStocks from '@/routes/item-stocks';
import items from '@/routes/items';
import purchaseOrders from '@/routes/purchase-orders';
import salesOrders from '@/routes/sales-orders';
import warehouses from '@/routes/warehouses';
import type { BreadcrumbItem } from '@/types';
import {
    formatAmount,
    formatQuantity,
    type ItemStock,
} from './types/ItemStock';

export interface ActiveReservation {
    id: string;
    sales_order_id: string;
    order_code: string;
    client_name: string;
    client_document?: string | null;
    order_date: string;
    reserved_quantity: number;
    ordered_quantity: number;
    dispatched_quantity: number;
    status: string;
}

export interface IncomingOrder {
    id: string;
    purchase_order_id: string;
    order_code: string;
    supplier_name: string;
    supplier_document?: string | null;
    order_date: string;
    expected_date?: string | null;
    pending_quantity: number;
    ordered_quantity: number;
    received_quantity: number;
    status: string;
}

interface Props {
    stock: ItemStock;
    activeReservations?: ActiveReservation[];
    incomingOrders?: IncomingOrder[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

interface BalanceRowProps {
    label: string;
    value: string;
    hint?: string;
    valueClassName?: string;
}

function BalanceRow({ label, value, hint, valueClassName = '' }: BalanceRowProps) {
    return (
        <div className="flex items-center justify-between gap-4 text-[13.5px]">
            <span className="font-medium text-muted-foreground">
                {label}
                {hint && (
                    <span className="ml-1.5 text-[12px] opacity-70">
                        ({hint})
                    </span>
                )}
            </span>
            <b className={`font-bold tabular-nums ${valueClassName}`}>{value}</b>
        </div>
    );
}

export default function ItemStocksShow({
    stock,
    activeReservations = [],
    incomingOrders = [],
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({
        status: stock.status === 'active' ? 'inactive' : 'active',
    });

    const title = stock.item_name ?? 'Existencia';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Existencias', href: itemStocks.index(companyId).url },
        {
            title,
            href: itemStocks.show({ company: companyId, id: stock.id }).url,
        },
    ];

    const handleToggleStatus = () => {
        put(itemStocks.updateStatus({ company: companyId, id: stock.id }).url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={itemStocks.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Existencias
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {title}
                            </h1>
                            <StatusPill
                                kind={
                                    stock.status === 'inactive'
                                        ? 'inactivo'
                                        : 'activo'
                                }
                            />
                        </div>
                        <div className="flex flex-wrap items-center gap-x-4 gap-y-1.5">
                            {stock.item_code && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {stock.item_code}
                                </span>
                            )}
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <WarehouseIcon className="size-3.5 opacity-80" />
                                {stock.warehouse_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <MapPin className="size-3.5 opacity-80" />
                                {stock.location_code ??
                                    stock.location_name ??
                                    '—'}
                            </span>
                            {stock.lot_number && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Layers className="size-3.5 opacity-80" />
                                    Lote {stock.lot_number}
                                </span>
                            )}
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Clock className="size-3.5 opacity-80" />
                                {stock.last_movement_at ?? 'Sin movimientos'}
                            </span>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        <Link
                            href={
                                items.show({
                                    company: companyId,
                                    id: stock.item_id,
                                }).url
                            }
                        >
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            >
                                <Package />
                                Artículo
                            </Button>
                        </Link>
                        <Link
                            href={
                                warehouses.show({
                                    company: companyId,
                                    id: stock.warehouse_id,
                                }).url
                            }
                        >
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            >
                                <WarehouseIcon />
                                Bodega
                            </Button>
                        </Link>
                        {stock.lot_id && (
                            <Link
                                href={
                                    itemLots.show({
                                        company: companyId,
                                        id: stock.lot_id,
                                    }).url
                                }
                            >
                                <Button
                                    variant="outline"
                                    className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                >
                                    <Layers />
                                    Lote
                                </Button>
                            </Link>
                        )}
                        <Button
                            variant="outline"
                            className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            onClick={handleToggleStatus}
                            disabled={processing}
                        >
                            <Power />
                            {stock.status === 'active'
                                ? 'Retirar saldo'
                                : 'Reactivar'}
                        </Button>
                    </div>
                </Card>

                <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl p-5">
                        <div className="text-base font-bold tracking-tight">
                            Desglose de Saldo
                        </div>
                        <div className="flex flex-col gap-2.5">
                            <BalanceRow
                                label="Existencia física"
                                hint="en bodega"
                                value={formatQuantity(stock.quantity)}
                            />
                            <BalanceRow
                                label="Comprometida / Reservada"
                                hint="pedidos de venta confirmados"
                                value={formatQuantity(stock.reserved_quantity)}
                                valueClassName={
                                    stock.reserved_quantity > 0
                                        ? 'text-amber-600 dark:text-amber-400'
                                        : ''
                                }
                            />
                            <BalanceRow
                                label="En camino"
                                hint="compras pendientes de entrega"
                                value={formatQuantity(stock.incoming_quantity)}
                                valueClassName={
                                    stock.incoming_quantity > 0
                                        ? 'text-sky-600 dark:text-sky-400'
                                        : ''
                                }
                            />
                            <div className="mt-1 border-t pt-2.5">
                                <BalanceRow
                                    label="Disponible para venta"
                                    hint="físico menos reservado"
                                    value={formatQuantity(
                                        stock.available_quantity,
                                    )}
                                    valueClassName="text-base font-extrabold text-foreground"
                                />
                            </div>
                        </div>
                    </Card>

                    <Card className="gap-3 rounded-2xl p-5">
                        <div className="text-base font-bold tracking-tight">
                            Valuación
                        </div>
                        <div className="flex flex-col gap-2.5">
                            <BalanceRow
                                label="Costo promedio"
                                hint="en esta bodega"
                                value={formatAmount(stock.average_cost)}
                            />
                            <BalanceRow
                                label="Valor del inventario"
                                value={formatAmount(stock.total_value)}
                            />
                        </div>
                        <p className="mt-1 text-[12.5px] text-muted-foreground">
                            El saldo es derivado del kardex: se corrige con un
                            Ajuste de inventario, nunca editando esta pantalla.
                        </p>
                    </Card>
                </div>

                {/* TABLA DE ÓRDENES DE VENTA QUE RESERVAN ESTE ARTÍCULO */}
                <Card className="gap-4 rounded-2xl p-5">
                    <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-2.5">
                            <div className="flex size-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                                <ShoppingCart className="size-4" />
                            </div>
                            <div>
                                <h2 className="text-base font-bold tracking-tight">
                                    Órdenes de Venta que reservan este artículo
                                </h2>
                                <p className="text-xs text-muted-foreground">
                                    Pedidos confirmados que apartan inventario en esta bodega antes del despacho físico.
                                </p>
                            </div>
                        </div>
                        {activeReservations.length > 0 && (
                            <span className="inline-flex w-max items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">
                                {formatQuantity(stock.reserved_quantity)} unid. reservadas
                            </span>
                        )}
                    </div>

                    {activeReservations.length > 0 ? (
                        <div className="overflow-x-auto rounded-xl border">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-muted text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                                    <tr>
                                        <th className="py-2.5 px-3">Pedido</th>
                                        <th className="py-2.5 px-3">Cliente</th>
                                        <th className="py-2.5 px-3">Fecha</th>
                                        <th className="py-2.5 px-3 text-right">Reservado</th>
                                        <th className="py-2.5 px-3 text-right">Despachado / Pedido</th>
                                        <th className="py-2.5 px-3 text-center">Estado</th>
                                        <th className="py-2.5 px-3 text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {activeReservations.map((res) => (
                                        <tr key={res.id} className="transition-colors hover:bg-muted/50">
                                            <td className="py-2.5 px-3 font-bold">
                                                <Link
                                                    href={salesOrders.show({ company: companyId, id: res.sales_order_id }).url}
                                                    className="text-primary hover:underline"
                                                >
                                                    {res.order_code}
                                                </Link>
                                            </td>
                                            <td className="py-2.5 px-3">
                                                <div className="font-semibold text-foreground">{res.client_name}</div>
                                                {res.client_document && (
                                                    <div className="text-[11px] text-muted-foreground">{res.client_document}</div>
                                                )}
                                            </td>
                                            <td className="py-2.5 px-3 text-xs text-muted-foreground">{res.order_date}</td>
                                            <td className="py-2.5 px-3 text-right font-extrabold text-amber-700 dark:text-amber-400 tabular-nums">
                                                {formatQuantity(res.reserved_quantity)}
                                            </td>
                                            <td className="py-2.5 px-3 text-right text-xs text-muted-foreground tabular-nums">
                                                {formatQuantity(res.dispatched_quantity)} / {formatQuantity(res.ordered_quantity)}
                                            </td>
                                            <td className="py-2.5 px-3 text-center">
                                                <span className="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 capitalize dark:bg-zinc-800 dark:text-zinc-300">
                                                    {res.status === 'confirmed' ? 'Confirmado' : res.status}
                                                </span>
                                            </td>
                                            <td className="py-2.5 px-3 text-right">
                                                <Link
                                                    href={salesOrders.show({ company: companyId, id: res.sales_order_id }).url}
                                                    className="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline"
                                                >
                                                    <span>Ver orden</span>
                                                    <ExternalLink className="size-3" />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="rounded-xl border border-dashed p-4 text-center text-xs text-muted-foreground">
                            No hay pedidos activos reservando este artículo. Toda la existencia física en esta bodega está disponible.
                        </div>
                    )}
                </Card>

                {/* TABLA DE COMPRAS EN CAMINO (SI HAY) */}
                {incomingOrders.length > 0 && (
                    <Card className="gap-4 rounded-2xl p-5">
                        <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-2.5">
                                <div className="flex size-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400">
                                    <Truck className="size-4" />
                                </div>
                                <div>
                                    <h2 className="text-base font-bold tracking-tight">
                                        Órdenes de Compra en camino (por recibir)
                                    </h2>
                                    <p className="text-xs text-muted-foreground">
                                        Compras confirmadas a proveedores que repondrán este inventario.
                                    </p>
                                </div>
                            </div>
                            <span className="inline-flex w-max items-center rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-bold text-sky-800 dark:bg-sky-950/50 dark:text-sky-300">
                                {formatQuantity(stock.incoming_quantity)} unid. por recibir
                            </span>
                        </div>

                        <div className="overflow-x-auto rounded-xl border">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-muted text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                                    <tr>
                                        <th className="py-2.5 px-3">Orden de Compra</th>
                                        <th className="py-2.5 px-3">Proveedor</th>
                                        <th className="py-2.5 px-3">Fecha</th>
                                        <th className="py-2.5 px-3 text-right">Por Recibir</th>
                                        <th className="py-2.5 px-3 text-right">Recibido / Pedido</th>
                                        <th className="py-2.5 px-3 text-center">Estado</th>
                                        <th className="py-2.5 px-3 text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {incomingOrders.map((inc) => (
                                        <tr key={inc.id} className="transition-colors hover:bg-muted/50">
                                            <td className="py-2.5 px-3 font-bold">
                                                <Link
                                                    href={purchaseOrders.show({ company: companyId, id: inc.purchase_order_id }).url}
                                                    className="text-primary hover:underline"
                                                >
                                                    {inc.order_code}
                                                </Link>
                                            </td>
                                            <td className="py-2.5 px-3">
                                                <div className="font-semibold text-foreground">{inc.supplier_name}</div>
                                                {inc.supplier_document && (
                                                    <div className="text-[11px] text-muted-foreground">{inc.supplier_document}</div>
                                                )}
                                            </td>
                                            <td className="py-2.5 px-3 text-xs text-muted-foreground">
                                                {inc.expected_date ? `Esp: ${inc.expected_date}` : inc.order_date}
                                            </td>
                                            <td className="py-2.5 px-3 text-right font-extrabold text-sky-700 dark:text-sky-400 tabular-nums">
                                                {formatQuantity(inc.pending_quantity)}
                                            </td>
                                            <td className="py-2.5 px-3 text-right text-xs text-muted-foreground tabular-nums">
                                                {formatQuantity(inc.received_quantity)} / {formatQuantity(inc.ordered_quantity)}
                                            </td>
                                            <td className="py-2.5 px-3 text-center">
                                                <span className="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 capitalize dark:bg-zinc-800 dark:text-zinc-300">
                                                    {inc.status === 'confirmed' ? 'Confirmado' : inc.status}
                                                </span>
                                            </td>
                                            <td className="py-2.5 px-3 text-right">
                                                <Link
                                                    href={purchaseOrders.show({ company: companyId, id: inc.purchase_order_id }).url}
                                                    className="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline"
                                                >
                                                    <span>Ver orden</span>
                                                    <ExternalLink className="size-3" />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
