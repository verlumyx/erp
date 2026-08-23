import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarCheck,
    Edit,
    Hash,
    Layers,
    Package,
    Power,
    Warehouse as WarehouseIcon,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import itemLots from '@/routes/item-lots';
import itemSerials from '@/routes/item-serials';
import items from '@/routes/items';
import warehouses from '@/routes/warehouses';
import type { BreadcrumbItem } from '@/types';
import {
    SERIAL_STATUS_LABELS,
    SERIAL_STATUS_PILL,
    type ItemSerial,
    type ItemSerialStatus,
} from './types/ItemSerial';

interface Props {
    serial: ItemSerial;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ItemSerialsShow({ serial }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Series', href: itemSerials.index(companyId).url },
        {
            title: serial.serial_number,
            href: itemSerials.show({ company: companyId, id: serial.id }).url,
        },
    ];

    const handleChangeStatus = (status: ItemSerialStatus) => {
        router.put(
            itemSerials.updateStatus({ company: companyId, id: serial.id }).url,
            { status },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={serial.serial_number} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={itemSerials.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Series
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {serial.serial_number}
                            </h1>
                            <StatusPill
                                kind={SERIAL_STATUS_PILL[serial.status]}
                            >
                                {SERIAL_STATUS_LABELS[serial.status]}
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Hash className="size-3.5 opacity-80" />
                                {serial.code}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Package className="size-3.5 opacity-80" />
                                {serial.item_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <WarehouseIcon className="size-3.5 opacity-80" />
                                {serial.warehouse_name ?? 'Sin bodega'}
                            </span>
                            {serial.sold_at && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <CalendarCheck className="size-3.5 opacity-80" />
                                    Salió el {serial.sold_at}
                                </span>
                            )}
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        <Link
                            href={
                                items.show({
                                    company: companyId,
                                    id: serial.item_id,
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
                        {serial.lot_id && (
                            <Link
                                href={
                                    itemLots.show({
                                        company: companyId,
                                        id: serial.lot_id,
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
                        {serial.warehouse_id && (
                            <Link
                                href={
                                    warehouses.show({
                                        company: companyId,
                                        id: serial.warehouse_id,
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
                        )}
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="outline"
                                    className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                >
                                    <Power />
                                    Cambiar estado
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                {(
                                    Object.entries(
                                        SERIAL_STATUS_LABELS,
                                    ) as Array<[ItemSerialStatus, string]>
                                )
                                    .filter(
                                        ([status]) => status !== serial.status,
                                    )
                                    .map(([status, label]) => (
                                        <DropdownMenuItem
                                            key={status}
                                            onClick={() =>
                                                handleChangeStatus(status)
                                            }
                                        >
                                            {label}
                                        </DropdownMenuItem>
                                    ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <Link
                            href={
                                itemSerials.edit({
                                    company: companyId,
                                    id: serial.id,
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
                    </div>
                </Card>

                <Card className="gap-3 rounded-2xl p-5">
                    <div className="text-base font-bold tracking-tight">
                        Trazabilidad
                    </div>
                    <div className="flex flex-col gap-2.5">
                        <div className="flex items-center justify-between text-[13.5px]">
                            <span className="font-medium text-muted-foreground">
                                Artículo
                            </span>
                            <b className="font-bold">
                                {serial.item_code
                                    ? `${serial.item_code} · ${serial.item_name}`
                                    : (serial.item_name ?? '—')}
                            </b>
                        </div>
                        <div className="flex items-center justify-between text-[13.5px]">
                            <span className="font-medium text-muted-foreground">
                                Lote
                            </span>
                            <b className="font-bold">
                                {serial.lot_number ?? 'Sin lote'}
                            </b>
                        </div>
                        <div className="flex items-center justify-between text-[13.5px]">
                            <span className="font-medium text-muted-foreground">
                                Bodega
                            </span>
                            <b className="font-bold">
                                {serial.warehouse_name ?? 'Sin bodega'}
                            </b>
                        </div>
                        <div className="flex items-center justify-between text-[13.5px]">
                            <span className="font-medium text-muted-foreground">
                                Salida definitiva
                            </span>
                            <b className="font-bold tabular-nums">
                                {serial.sold_at ?? '—'}
                            </b>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
