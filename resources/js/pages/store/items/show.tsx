import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Edit,
    ExternalLink,
    Hash,
    ImageOff,
    Link2,
    Power,
    Star,
    Tag,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/lib/money';
import items from '@/routes/items';
import storeItems from '@/routes/store-items';
import type { BreadcrumbItem } from '@/types';
import { useStorePermissions } from '../hooks/useStorePermissions';
import type { StoreItem } from '../types/Store';

interface Props {
    store_item: StoreItem;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
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

export default function StoreItemsShow({ store_item }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const { can } = useStorePermissions();

    const images = [...store_item.images]
        .filter((image) => image.status === 'active')
        .sort((a, b) => a.order - b.order);

    const toggleStatus = () =>
        router.put(
            storeItems.updateStatus({ company: companyId, id: store_item.id })
                .url,
            { status: store_item.status === 'active' ? 'inactive' : 'active' },
            { preserveScroll: true },
        );

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Publicaciones', href: storeItems.index(companyId).url },
        {
            title: store_item.code,
            href: storeItems.show({ company: companyId, id: store_item.id })
                .url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={store_item.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={storeItems.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Publicaciones
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex items-center gap-[18px]">
                        <div className="grid size-16 shrink-0 place-items-center overflow-hidden rounded-[12px] border bg-muted">
                            {images[0] ? (
                                <img
                                    src={images[0].url}
                                    alt={images[0].alt_text ?? store_item.title}
                                    className="size-full object-cover"
                                />
                            ) : (
                                <ImageOff className="size-5 text-muted-foreground" />
                            )}
                        </div>
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-extrabold tracking-tight">
                                    {store_item.title}
                                </h1>
                                <StatusPill
                                    kind={
                                        store_item.status === 'active'
                                            ? 'activo'
                                            : 'inactivo'
                                    }
                                >
                                    {store_item.status === 'active'
                                        ? 'Visible'
                                        : 'Oculta'}
                                </StatusPill>
                                {store_item.is_featured === 'yes' && (
                                    <span className="inline-flex items-center gap-1 rounded-full bg-warn-soft px-2.5 py-1 text-[12.5px] font-bold text-warn">
                                        <Star className="size-3" />
                                        Destacado
                                    </span>
                                )}
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {store_item.code}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Link2 className="size-3.5 opacity-80" />
                                    /productos/{store_item.slug}
                                </span>
                                {store_item.item?.category && (
                                    <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                        <Tag className="size-3.5 opacity-80" />
                                        {store_item.item.category.name}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        {can('store-items.update-status') && (
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                onClick={toggleStatus}
                            >
                                <Power />
                                {store_item.status === 'active'
                                    ? 'Ocultar'
                                    : 'Mostrar'}
                            </Button>
                        )}
                        {can('store-items.edit') && (
                            <Link
                                href={
                                    storeItems.edit({
                                        company: companyId,
                                        id: store_item.id,
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

                <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Artículo del ERP
                        </div>
                        <DataRow
                            label="Artículo"
                            value={
                                store_item.item ? (
                                    <Link
                                        href={
                                            items.show({
                                                company: companyId,
                                                id: store_item.item.id,
                                            }).url
                                        }
                                        className="inline-flex items-center gap-1 text-primary hover:underline"
                                    >
                                        {store_item.item.code} —{' '}
                                        {store_item.item.name}
                                        <ExternalLink className="size-3.5" />
                                    </Link>
                                ) : (
                                    '—'
                                )
                            }
                        />
                        <DataRow
                            label="SKU"
                            value={store_item.item?.sku ?? '—'}
                        />
                        <DataRow
                            label="Precio en la tienda"
                            value={
                                store_item.price
                                    ? formatMoney(
                                          store_item.price.amount,
                                          store_item.price.currency,
                                      )
                                    : 'Consultar'
                            }
                        />
                        <DataRow
                            label="Disponibilidad"
                            value={
                                store_item.availability
                                    ? store_item.availability.in_stock === 'yes'
                                        ? `Disponible${store_item.availability.quantity ? ` · ${store_item.availability.quantity}` : ''}`
                                        : 'Agotado'
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Estado del artículo"
                            value={
                                store_item.item
                                    ? `${store_item.item.status === 'active' ? 'Activo' : 'Inactivo'} · ${store_item.item.is_sellable === 'yes' ? 'vendible' : 'no vendible'}`
                                    : '—'
                            }
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Publicación
                        </div>
                        <DataRow
                            label="Orden"
                            value={String(store_item.order)}
                        />
                        <DataRow
                            label="Publicada por primera vez"
                            value={store_item.published_at ?? 'Nunca'}
                        />
                        <DataRow
                            label="Resumen"
                            value={store_item.summary ?? '—'}
                        />
                        <DataRow
                            label="Fotos activas"
                            value={String(images.length)}
                        />
                    </Card>
                </div>

                {store_item.description && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Descripción
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {store_item.description}
                        </p>
                    </Card>
                )}

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="border-b p-5 text-[13px] font-bold text-muted-foreground">
                        Galería
                    </div>
                    {images.length === 0 ? (
                        <p className="p-8 text-center text-sm text-muted-foreground">
                            Sin fotos activas.
                        </p>
                    ) : (
                        <div className="grid grid-cols-2 gap-4 p-5 sm:grid-cols-3 lg:grid-cols-4">
                            {images.map((image, index) => (
                                <div
                                    key={image.id}
                                    className="relative aspect-square overflow-hidden rounded-[12px] border bg-muted"
                                >
                                    <img
                                        src={image.url}
                                        alt={image.alt_text ?? ''}
                                        className="size-full object-cover"
                                    />
                                    {index === 0 && (
                                        <span className="absolute top-2 left-2 rounded-full bg-primary px-2 py-0.5 text-[11.5px] font-bold text-primary-foreground">
                                            Portada
                                        </span>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
