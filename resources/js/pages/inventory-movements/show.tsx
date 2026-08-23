import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowDownLeft,
    ArrowLeft,
    ArrowUpRight,
    Barcode,
    Clock,
    FileText,
    Hash,
    Layers,
    MapPin,
    Package,
    Warehouse as WarehouseIcon,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import inventoryMovements from '@/routes/inventory-movements';
import itemLots from '@/routes/item-lots';
import items from '@/routes/items';
import warehouses from '@/routes/warehouses';
import type { BreadcrumbItem } from '@/types';
import {
    formatAmount,
    formatCost,
    formatQuantity,
    MOVEMENT_TYPE_LABELS,
    originLabel,
    signedQuantity,
    type InventoryMovement,
} from './types/InventoryMovement';

interface Props {
    movement: InventoryMovement;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

interface DetailRowProps {
    label: string;
    value: string;
    hint?: string;
}

function DetailRow({ label, value, hint }: DetailRowProps) {
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

export default function InventoryMovementsShow({ movement }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const inbound = movement.direction === 'in';
    const title = MOVEMENT_TYPE_LABELS[movement.type];

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kardex', href: inventoryMovements.index(companyId).url },
        {
            title: movement.code,
            href: inventoryMovements.show({
                company: companyId,
                id: movement.id,
            }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={movement.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={inventoryMovements.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Kardex
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <span
                                className={`flex size-9 shrink-0 items-center justify-center rounded-[11px] ${
                                    inbound
                                        ? 'bg-ok-soft text-ok'
                                        : 'bg-bad-soft text-bad'
                                }`}
                            >
                                {inbound ? (
                                    <ArrowDownLeft className="size-5" />
                                ) : (
                                    <ArrowUpRight className="size-5" />
                                )}
                            </span>
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {title}
                            </h1>
                            {movement.status === 'reversed' && (
                                <StatusPill kind="inactivo">
                                    Revertido
                                </StatusPill>
                            )}
                            {movement.reversal_of_id && (
                                <StatusPill kind="libre">
                                    Contrapartida
                                </StatusPill>
                            )}
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Hash className="size-3.5 opacity-80" />
                                {movement.code}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Clock className="size-3.5 opacity-80" />
                                {movement.movement_date ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <FileText className="size-3.5 opacity-80" />
                                {originLabel(movement.origin_type)}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <WarehouseIcon className="size-3.5 opacity-80" />
                                {movement.warehouse_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <MapPin className="size-3.5 opacity-80" />
                                {movement.location_code ??
                                    movement.location_name ??
                                    '—'}
                            </span>
                            {movement.lot_number && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Layers className="size-3.5 opacity-80" />
                                    Lote {movement.lot_number}
                                </span>
                            )}
                            {movement.serial_number && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Barcode className="size-3.5 opacity-80" />
                                    Serie {movement.serial_number}
                                </span>
                            )}
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        <Link
                            href={
                                items.show({
                                    company: companyId,
                                    id: movement.item_id,
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
                                    id: movement.warehouse_id,
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
                        {movement.lot_id && (
                            <Link
                                href={
                                    itemLots.show({
                                        company: companyId,
                                        id: movement.lot_id,
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
                    </div>
                </Card>

                <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <Card className="gap-3 rounded-2xl p-5">
                        <div className="text-base font-bold tracking-tight">
                            Artículo
                        </div>
                        <div className="flex flex-col gap-1">
                            <span className="font-bold">
                                {movement.item_name ?? '—'}
                            </span>
                            <span className="text-[12.5px] text-muted-foreground">
                                {movement.item_code ?? '—'}
                                {movement.item_sku
                                    ? ` · ${movement.item_sku}`
                                    : ''}
                            </span>
                        </div>
                    </Card>

                    <Card className="gap-3 rounded-2xl p-5">
                        <div className="text-base font-bold tracking-tight">
                            Movimiento
                        </div>
                        <div className="flex flex-col gap-2.5">
                            <DetailRow
                                label="Cantidad"
                                hint="en unidad base"
                                value={signedQuantity(movement)}
                            />
                            <DetailRow
                                label="Costo unitario"
                                value={formatCost(movement.unit_cost)}
                            />
                            <div className="mt-1 border-t pt-2.5">
                                <DetailRow
                                    label="Costo total"
                                    value={formatAmount(movement.total_cost)}
                                />
                            </div>
                        </div>
                    </Card>

                    <Card className="gap-3 rounded-2xl p-5">
                        <div className="text-base font-bold tracking-tight">
                            Saldo después
                        </div>
                        <div className="flex flex-col gap-2.5">
                            <DetailRow
                                label="Existencia"
                                hint="en esta bodega"
                                value={formatQuantity(
                                    movement.balance_quantity,
                                )}
                            />
                            <DetailRow
                                label="Costo promedio"
                                value={formatCost(movement.balance_cost)}
                            />
                            <div className="mt-1 border-t pt-2.5">
                                <DetailRow
                                    label="Valor del inventario"
                                    value={formatAmount(movement.balance_value)}
                                />
                            </div>
                        </div>
                        <p className="mt-1 text-[12.5px] text-muted-foreground">
                            El saldo quedó congelado al registrar el movimiento:
                            un movimiento posterior no lo reescribe.
                        </p>
                    </Card>
                </div>

                {(movement.reversal_of_id ||
                    movement.reversal_id ||
                    movement.notes) && (
                    <Card className="gap-3 rounded-2xl p-5">
                        <div className="text-base font-bold tracking-tight">
                            Trazabilidad
                        </div>
                        <div className="flex flex-col gap-2.5">
                            {movement.reversal_of_id && (
                                <div className="flex items-center justify-between gap-4 text-[13.5px]">
                                    <span className="font-medium text-muted-foreground">
                                        Revierte al movimiento
                                    </span>
                                    <Link
                                        href={
                                            inventoryMovements.show({
                                                company: companyId,
                                                id: movement.reversal_of_id,
                                            }).url
                                        }
                                        className="font-bold text-primary hover:underline"
                                    >
                                        {movement.reversal_of_code ?? 'Ver'}
                                    </Link>
                                </div>
                            )}
                            {movement.reversal_id && (
                                <div className="flex items-center justify-between gap-4 text-[13.5px]">
                                    <span className="font-medium text-muted-foreground">
                                        Anulado por
                                    </span>
                                    <Link
                                        href={
                                            inventoryMovements.show({
                                                company: companyId,
                                                id: movement.reversal_id,
                                            }).url
                                        }
                                        className="font-bold text-primary hover:underline"
                                    >
                                        {movement.reversal_code ?? 'Ver'}
                                    </Link>
                                </div>
                            )}
                            {movement.notes && (
                                <p className="text-[13.5px] text-muted-foreground">
                                    {movement.notes}
                                </p>
                            )}
                        </div>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
