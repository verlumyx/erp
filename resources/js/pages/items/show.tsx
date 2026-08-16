import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Barcode,
    Edit,
    Hash,
    Power,
    Ruler,
    StickyNote,
    Tag,
    Tags,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import items from '@/routes/items';
import type { BreadcrumbItem } from '@/types';
import { COST_METHOD_LABELS, ITEM_TYPE_LABELS, type Item } from './types/Item';

interface Props {
    item: Item;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function DataRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-4 text-[13.5px]">
            <span className="font-medium text-muted-foreground">{label}</span>
            <b className="text-right font-bold">{value}</b>
        </div>
    );
}

export default function ItemsShow({ item }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({
        status: item.status === 'active' ? 'inactive' : 'active',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Catálogo de artículos', href: items.index(companyId).url },
        {
            title: item.name,
            href: items.show({ company: companyId, id: item.id }).url,
        },
    ];

    const activeUnits = (item.units ?? []).filter(
        (unit) => unit.status === 'active',
    );
    const activePrices = (item.prices ?? []).filter(
        (price) => price.status === 'active',
    );

    const handleToggleStatus = () => {
        put(items.updateStatus({ company: companyId, id: item.id }).url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={item.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={items.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Catálogo de artículos
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {item.name}
                            </h1>
                            <StatusPill
                                kind={
                                    item.status === 'inactive'
                                        ? 'inactivo'
                                        : 'activo'
                                }
                            />
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Hash className="size-3.5 opacity-80" />
                                {item.code}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Tag className="size-3.5 opacity-80" />
                                {item.sku}
                            </span>
                            {item.barcode && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Barcode className="size-3.5 opacity-80" />
                                    {item.barcode}
                                </span>
                            )}
                            {item.category_name && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Tags className="size-3.5 opacity-80" />
                                    {item.category_name}
                                </span>
                            )}
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        <Button
                            variant="outline"
                            className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            onClick={handleToggleStatus}
                            disabled={processing}
                        >
                            <Power />
                            {item.status === 'active'
                                ? 'Desactivar'
                                : 'Activar'}
                        </Button>
                        <Link
                            href={
                                items.edit({ company: companyId, id: item.id })
                                    .url
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

                <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Clasificación
                        </div>
                        <DataRow
                            label="Tipo"
                            value={ITEM_TYPE_LABELS[item.type]}
                        />
                        <DataRow
                            label="Método de costo"
                            value={COST_METHOD_LABELS[item.cost_method]}
                        />
                        <DataRow
                            label="Se compra"
                            value={item.is_purchasable === 'yes' ? 'Sí' : 'No'}
                        />
                        <DataRow
                            label="Se vende"
                            value={item.is_sellable === 'yes' ? 'Sí' : 'No'}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Costos y reposición
                        </div>
                        <DataRow
                            label="Costo estándar"
                            value={item.standard_cost}
                        />
                        <DataRow
                            label="Costo promedio"
                            value={item.average_cost}
                        />
                        <DataRow label="Precio mínimo" value={item.min_price} />
                        <DataRow
                            label="Existencia mínima / máxima"
                            value={`${item.min_stock} / ${item.max_stock}`}
                        />
                        <DataRow
                            label="Cantidad a reordenar"
                            value={item.reorder_quantity}
                        />
                    </Card>
                </div>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="flex items-center gap-2 border-b p-5 text-[13px] font-bold text-muted-foreground">
                        <Ruler className="size-[15px]" />
                        Unidades del artículo
                    </div>
                    <div className="flex flex-col">
                        {activeUnits.map((unit) => (
                            <div
                                key={unit.id}
                                className="flex items-center justify-between gap-4 border-b px-5 py-3 last:border-b-0"
                            >
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {unit.measurement_unit_name ?? '—'}
                                        {unit.measurement_unit_abbreviation
                                            ? ` (${unit.measurement_unit_abbreviation})`
                                            : ''}
                                    </span>
                                    <span className="text-[12.5px] text-muted-foreground">
                                        Factor de conversión:{' '}
                                        {unit.conversion_factor}
                                    </span>
                                </div>
                                {unit.is_base === 'yes' && (
                                    <span className="rounded-full bg-primary-soft px-2.5 py-1 text-[12.5px] font-bold text-primary">
                                        Unidad base
                                    </span>
                                )}
                            </div>
                        ))}
                        {activeUnits.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                El artículo no tiene unidades activas.
                            </div>
                        )}
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="flex items-center gap-2 border-b p-5 text-[13px] font-bold text-muted-foreground">
                        <Tags className="size-[15px]" />
                        Precios por lista
                    </div>
                    <div className="flex flex-col">
                        {activePrices.map((price) => (
                            <div
                                key={price.id}
                                className="flex flex-wrap items-center justify-between gap-4 border-b px-5 py-3 last:border-b-0"
                            >
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {price.price_list_name ?? '—'}
                                    </span>
                                </div>
                                <b className="font-bold tabular-nums">
                                    {price.price} {price.currency}
                                </b>
                            </div>
                        ))}
                        {activePrices.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                El artículo no tiene precios de lista activos.
                            </div>
                        )}
                    </div>
                </Card>

                {(item.description || item.notes) && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            {item.description ? 'Descripción' : 'Notas'}
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {item.description ?? item.notes}
                        </p>
                        {item.description && item.notes && (
                            <p className="mt-2 text-sm leading-relaxed whitespace-pre-wrap text-muted-foreground">
                                {item.notes}
                            </p>
                        )}
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
