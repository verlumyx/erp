import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Clock,
    Hash,
    Layers,
    MapPin,
    Package,
    Power,
    Warehouse as WarehouseIcon,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import itemLots from '@/routes/item-lots';
import itemStocks from '@/routes/item-stocks';
import items from '@/routes/items';
import warehouses from '@/routes/warehouses';
import type { BreadcrumbItem } from '@/types';
import {
    formatAmount,
    formatQuantity,
    type ItemStock,
} from './types/ItemStock';

interface Props {
    stock: ItemStock;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

interface BalanceRowProps {
    label: string;
    value: string;
    hint?: string;
}

function BalanceRow({ label, value, hint }: BalanceRowProps) {
    return (
        <div className="flex items-center justify-between gap-4 text-[13.5px]">
            <span className="font-medium text-muted-foreground">
                {label}
                {hint && (
                    <span className="ml-1.5 text-[12px] opacity-70">
                        {hint}
                    </span>
                )}
            </span>
            <b className="font-bold tabular-nums">{value}</b>
        </div>
    );
}

export default function ItemStocksShow({ stock }: Props) {
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
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Hash className="size-3.5 opacity-80" />
                                {stock.item_code ?? '—'}
                            </span>
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
                            Saldo
                        </div>
                        <div className="flex flex-col gap-2.5">
                            <BalanceRow
                                label="Existencia física"
                                value={formatQuantity(stock.quantity)}
                            />
                            <BalanceRow
                                label="Comprometida"
                                hint="órdenes de venta confirmadas"
                                value={formatQuantity(stock.reserved_quantity)}
                            />
                            <BalanceRow
                                label="En camino"
                                hint="compras y traslados"
                                value={formatQuantity(stock.incoming_quantity)}
                            />
                            <div className="mt-1 border-t pt-2.5">
                                <BalanceRow
                                    label="Disponible"
                                    value={formatQuantity(
                                        stock.available_quantity,
                                    )}
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
                            Ajuste, nunca editando esta pantalla.
                        </p>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
