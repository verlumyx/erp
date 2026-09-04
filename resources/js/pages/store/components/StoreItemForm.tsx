import { Link, usePage } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import { Select2Ajax } from '@/components/select2-ajax';
import { FormLayout } from '@/components/form-layout';
import { FormActionBar, FormSummary } from '@/components/form-summary';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import { formatMoney } from '@/lib/money';
import items from '@/routes/items';
import { useStoreItemFormContext } from '../contexts/StoreItemFormContext';
import type { YesNo } from '../types/Store';

const YES_NO_OPTIONS: OptionType[] = [
    { value: 'yes', label: 'Sí' },
    { value: 'no', label: 'No' },
];

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

export function StoreItemForm() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const {
        mode,
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        itemOption,
        selectItem,
        lookupUrl,
        storeItem,
    } = useStoreItemFormContext();

    const source = storeItem?.item ?? null;

    return (
        <FormLayout onSubmit={handleSubmit}>
            <Card className="gap-4 rounded-2xl p-5">
                <div className="text-base font-bold tracking-tight">
                    Artículo
                </div>
                {mode === 'create' ? (
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="item_id"
                            className="text-[13px] font-semibold"
                        >
                            Artículo del catálogo *
                        </Label>
                        <Select2Ajax
                            inputId="item_id"
                            url={lookupUrl}
                            value={itemOption}
                            onChange={selectItem}
                            error={!!errors.item_id}
                            size="md"
                            placeholder="Busca por código, SKU o nombre"
                        />
                        <p className="text-[12.5px] text-muted-foreground">
                            Solo artículos activos, vendibles y que todavía no
                            están publicados.
                        </p>
                        {errors.item_id && (
                            <p className="text-sm text-bad">{errors.item_id}</p>
                        )}
                    </div>
                ) : (
                    <div className="flex flex-col gap-2.5">
                        <DataRow
                            label="Artículo"
                            value={
                                source ? (
                                    <Link
                                        href={
                                            items.show({
                                                company: companyId,
                                                id: source.id,
                                            }).url
                                        }
                                        className="inline-flex items-center gap-1 text-primary hover:underline"
                                    >
                                        {source.code} — {source.name}
                                        <ExternalLink className="size-3.5" />
                                    </Link>
                                ) : (
                                    '—'
                                )
                            }
                        />
                        <DataRow label="SKU" value={source?.sku ?? '—'} />
                        <DataRow
                            label="Categoría"
                            value={source?.category?.name ?? '—'}
                        />
                        <DataRow
                            label="Precio en la lista de la tienda"
                            value={
                                storeItem?.price
                                    ? formatMoney(
                                          storeItem.price.amount,
                                          storeItem.price.currency,
                                      )
                                    : 'Sin precio (la tienda muestra «Consultar»)'
                            }
                        />
                        <DataRow
                            label="Disponibilidad"
                            value={
                                storeItem?.availability
                                    ? storeItem.availability.in_stock === 'yes'
                                        ? `Disponible${storeItem.availability.quantity ? ` · ${storeItem.availability.quantity}` : ''}`
                                        : 'Agotado'
                                    : '—'
                            }
                        />
                        {source &&
                            (source.status !== 'active' ||
                                source.is_sellable !== 'yes') && (
                                <p className="rounded-[10px] border border-dashed border-warn p-3 text-[12.5px] text-warn">
                                    El artículo está inactivo o dejó de ser
                                    vendible: la publicación no sale en la
                                    tienda aunque esté activa.
                                </p>
                            )}
                    </div>
                )}
            </Card>

            <Card className="gap-4 rounded-2xl p-5">
                <div className="text-base font-bold tracking-tight">
                    Presentación
                </div>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="title"
                            className="text-[13px] font-semibold"
                        >
                            Título comercial *
                        </Label>
                        <Input
                            id="title"
                            value={data.title}
                            maxLength={150}
                            onChange={(e) => setData('title', e.target.value)}
                            className={`h-[42px] rounded-[10px] ${errors.title ? 'border-bad' : ''}`}
                        />
                        {errors.title && (
                            <p className="text-sm text-bad">{errors.title}</p>
                        )}
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="slug"
                            className="text-[13px] font-semibold"
                        >
                            Slug
                        </Label>
                        <Input
                            id="slug"
                            value={data.slug}
                            maxLength={160}
                            placeholder="Se genera del título"
                            onChange={(e) => setData('slug', e.target.value)}
                            className={`h-[42px] rounded-[10px] font-mono ${errors.slug ? 'border-bad' : ''}`}
                        />
                        <p className="text-[12.5px] text-muted-foreground">
                            Identificador en la URL: /productos/{'{slug}'}.
                            Minúsculas, números y guiones.
                        </p>
                        {errors.slug && (
                            <p className="text-sm text-bad">{errors.slug}</p>
                        )}
                    </div>
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label
                        htmlFor="summary"
                        className="text-[13px] font-semibold"
                    >
                        Resumen
                    </Label>
                    <Input
                        id="summary"
                        value={data.summary}
                        maxLength={300}
                        placeholder="Frase corta para la tarjeta del listado"
                        onChange={(e) => setData('summary', e.target.value)}
                        className={`h-[42px] rounded-[10px] ${errors.summary ? 'border-bad' : ''}`}
                    />
                    {errors.summary && (
                        <p className="text-sm text-bad">{errors.summary}</p>
                    )}
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label
                        htmlFor="description"
                        className="text-[13px] font-semibold"
                    >
                        Descripción
                    </Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        rows={8}
                        placeholder="Descripción larga. Admite Markdown simple."
                        onChange={(e) => setData('description', e.target.value)}
                        className={`rounded-[10px] ${errors.description ? 'border-bad' : ''}`}
                    />
                    {errors.description && (
                        <p className="text-sm text-bad">{errors.description}</p>
                    )}
                </div>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="is_featured"
                            className="text-[13px] font-semibold"
                        >
                            Destacado en la portada
                        </Label>
                        <Select2
                            inputId="is_featured"
                            options={YES_NO_OPTIONS}
                            value={
                                YES_NO_OPTIONS.find(
                                    (option) =>
                                        option.value === data.is_featured,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'is_featured',
                                    (option?.value ?? 'no') as YesNo,
                                )
                            }
                            error={!!errors.is_featured}
                            size="md"
                            isSearchable={false}
                        />
                        {errors.is_featured && (
                            <p className="text-sm text-bad">
                                {errors.is_featured}
                            </p>
                        )}
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="order"
                            className="text-[13px] font-semibold"
                        >
                            Orden
                        </Label>
                        <NumberInput
                            id="order"
                            value={data.order}
                            onValueChange={(value) => setData('order', value)}
                            min={0}
                            decimals={0}
                            className={`h-[42px] rounded-[10px] ${errors.order ? 'border-bad' : ''}`}
                        />
                        <p className="text-[12.5px] text-muted-foreground">
                            Posición dentro de su categoría; menor va primero.
                        </p>
                        {errors.order && (
                            <p className="text-sm text-bad">{errors.order}</p>
                        )}
                    </div>
                </div>
            </Card>

            <FormSummary>
                <DataRow
                    label="Artículo"
                    value={
                        mode === 'create'
                            ? (itemOption?.label ?? '—')
                            : (source?.code ?? '—')
                    }
                />
                <DataRow label="Título" value={data.title || '—'} />
                <DataRow
                    label="Destacado"
                    value={data.is_featured === 'yes' ? 'Sí' : 'No'}
                />
                {mode === 'edit' && (
                    <DataRow
                        label="Fotos activas"
                        value={String(
                            (storeItem?.images ?? []).filter(
                                (image) => image.status === 'active',
                            ).length,
                        )}
                    />
                )}
                {mode === 'create' && (
                    <p className="text-[12.5px] text-muted-foreground">
                        Las fotos se cargan después de crear la publicación, en
                        la pantalla de edición.
                    </p>
                )}
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={mode === 'create' ? 'Publicar' : 'Guardar cambios'}
            />
        </FormLayout>
    );
}
