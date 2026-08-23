import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarClock,
    Edit,
    Factory,
    Hash,
    Lock,
    Package,
    Truck,
    Unlock,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import itemLots from '@/routes/item-lots';
import items from '@/routes/items';
import type { BreadcrumbItem } from '@/types';
import {
    LOT_STATUS_LABELS,
    LOT_STATUS_PILL,
    type ItemLot,
} from './types/ItemLot';

interface Props {
    lot: ItemLot;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ItemLotsShow({ lot }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({
        status: lot.status === 'active' ? 'blocked' : 'active',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Lotes', href: itemLots.index(companyId).url },
        {
            title: lot.lot_number,
            href: itemLots.show({ company: companyId, id: lot.id }).url,
        },
    ];

    const handleToggleStatus = () => {
        put(itemLots.updateStatus({ company: companyId, id: lot.id }).url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={lot.lot_number} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={itemLots.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Lotes
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {lot.lot_number}
                            </h1>
                            <StatusPill kind={LOT_STATUS_PILL[lot.status]}>
                                {LOT_STATUS_LABELS[lot.status]}
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Hash className="size-3.5 opacity-80" />
                                {lot.code}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Package className="size-3.5 opacity-80" />
                                {lot.item_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Factory className="size-3.5 opacity-80" />
                                {lot.manufactured_at ?? 'Sin fabricación'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <CalendarClock className="size-3.5 opacity-80" />
                                {lot.expires_at ?? 'Sin vencimiento'}
                            </span>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        <Link
                            href={
                                items.show({
                                    company: companyId,
                                    id: lot.item_id,
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
                        {lot.status !== 'expired' && (
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                onClick={handleToggleStatus}
                                disabled={processing}
                            >
                                {lot.status === 'active' ? (
                                    <>
                                        <Lock />
                                        Retener
                                    </>
                                ) : (
                                    <>
                                        <Unlock />
                                        Liberar
                                    </>
                                )}
                            </Button>
                        )}
                        <Link
                            href={
                                itemLots.edit({
                                    company: companyId,
                                    id: lot.id,
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
                                {lot.item_code
                                    ? `${lot.item_code} · ${lot.item_name}`
                                    : (lot.item_name ?? '—')}
                            </b>
                        </div>
                        <div className="flex items-center justify-between text-[13.5px]">
                            <span className="inline-flex items-center gap-1.5 font-medium text-muted-foreground">
                                <Truck className="size-3.5 opacity-80" />
                                Proveedor
                            </span>
                            <b className="font-bold">
                                {lot.supplier_name ?? 'Lote interno'}
                            </b>
                        </div>
                        <div className="flex items-center justify-between text-[13.5px]">
                            <span className="font-medium text-muted-foreground">
                                Registrado
                            </span>
                            <b className="font-bold tabular-nums">
                                {lot.created_at}
                            </b>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
