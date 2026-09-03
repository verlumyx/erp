import { router, usePage } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    Eye,
    EyeOff,
    ImagePlus,
    Star,
    Upload,
} from 'lucide-react';
import { useRef, useState, type DragEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import storeItems from '@/routes/store-items';
import {
    MAX_ACTIVE_IMAGES,
    type StoreItem,
    type StoreItemImage,
} from '../types/Store';

interface StoreItemGalleryProps {
    storeItem: StoreItem;
    canEdit: boolean;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    errors?: Record<string, string>;
    [key: string]: unknown;
}

const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_BYTES = 5 * 1024 * 1024;

/**
 * Galería de la publicación: subir, reordenar y desactivar fotos. Vive en la
 * pantalla de edición, aparte del formulario, porque el envío con archivos
 * es distinto del JSON habitual. La de menor `order` es la portada.
 */
export function StoreItemGallery({
    storeItem,
    canEdit,
}: StoreItemGalleryProps) {
    const { currentCompany, errors: pageErrors } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const fileInput = useRef<HTMLInputElement>(null);
    const [dragging, setDragging] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);
    const [altText, setAltText] = useState('');

    const images = [...storeItem.images].sort((a, b) => a.order - b.order);
    const active = images.filter((image) => image.status === 'active');
    const inactive = images.filter((image) => image.status !== 'active');
    const remaining = MAX_ACTIVE_IMAGES - active.length;

    const routeArgs = { company: companyId, id: storeItem.id };

    const upload = (files: FileList | File[]) => {
        const list = Array.from(files);
        setLocalError(null);

        const rejected = list.find(
            (file) => !ACCEPTED.includes(file.type) || file.size > MAX_BYTES,
        );

        if (rejected) {
            setLocalError(
                `«${rejected.name}» no es JPG, PNG o WebP de hasta 5 MB.`,
            );

            return;
        }

        if (list.length === 0) {
            return;
        }

        if (list.length > remaining) {
            setLocalError(
                `Solo caben ${remaining} foto${remaining !== 1 ? 's' : ''} más: el máximo son ${MAX_ACTIVE_IMAGES} activas.`,
            );

            return;
        }

        setUploading(true);
        router.post(
            storeItems.images.store(routeArgs).url,
            { images: list, alt_text: altText || null },
            {
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => {
                    setUploading(false);
                    if (fileInput.current) {
                        fileInput.current.value = '';
                    }
                },
                onSuccess: () => setAltText(''),
            },
        );
    };

    const reorder = (ordered: StoreItemImage[]) => {
        router.put(
            storeItems.images.reorder(routeArgs).url,
            { order: ordered.map((image) => image.id) },
            { preserveScroll: true },
        );
    };

    const move = (index: number, delta: number) => {
        const target = index + delta;

        if (target < 0 || target >= active.length) {
            return;
        }

        const next = [...active];
        [next[index], next[target]] = [next[target], next[index]];
        reorder(next);
    };

    const toggleStatus = (image: StoreItemImage) => {
        router.put(
            storeItems.images.updateStatus({ ...routeArgs, image: image.id })
                .url,
            { status: image.status === 'active' ? 'inactive' : 'active' },
            { preserveScroll: true },
        );
    };

    /* Arrastrar una tarjeta sobre otra intercambia sus posiciones. */
    const [dragIndex, setDragIndex] = useState<number | null>(null);

    const onCardDrop = (index: number) => {
        if (dragIndex === null || dragIndex === index) {
            setDragIndex(null);

            return;
        }

        const next = [...active];
        const [moved] = next.splice(dragIndex, 1);
        next.splice(index, 0, moved);
        setDragIndex(null);
        reorder(next);
    };

    const onZoneDrop = (e: DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setDragging(false);

        if (!canEdit || dragIndex !== null) {
            return;
        }

        upload(e.dataTransfer.files);
    };

    const serverError =
        pageErrors?.images ??
        Object.entries(pageErrors ?? {}).find(([key]) =>
            key.startsWith('images.'),
        )?.[1] ??
        pageErrors?.order ??
        null;

    return (
        <Card className="gap-0 overflow-hidden rounded-2xl py-0">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b p-5">
                <div>
                    <div className="text-base font-bold tracking-tight">
                        Galería
                    </div>
                    <div className="mt-0.5 text-[13px] text-muted-foreground">
                        {active.length} de {MAX_ACTIVE_IMAGES} fotos activas. La
                        primera es la portada.
                    </div>
                </div>
            </div>

            <div className="flex flex-col gap-4 p-5">
                {canEdit && (
                    <div
                        onDragOver={(e) => {
                            e.preventDefault();
                            if (dragIndex === null) {
                                setDragging(true);
                            }
                        }}
                        onDragLeave={() => setDragging(false)}
                        onDrop={onZoneDrop}
                        className={cn(
                            'flex flex-col items-center justify-center gap-2 rounded-[12px] border border-dashed p-6 text-center transition-colors',
                            dragging && 'border-primary bg-primary-soft',
                            remaining <= 0 && 'opacity-60',
                        )}
                    >
                        <ImagePlus className="size-6 text-muted-foreground" />
                        <p className="text-[13.5px] font-semibold">
                            Arrastra las fotos aquí o
                        </p>
                        <input
                            ref={fileInput}
                            type="file"
                            multiple
                            accept={ACCEPTED.join(',')}
                            className="hidden"
                            onChange={(e) =>
                                e.target.files && upload(e.target.files)
                            }
                        />
                        <div className="flex flex-wrap items-center justify-center gap-2">
                            <div className="flex flex-col gap-1 text-left">
                                <Label
                                    htmlFor="gallery-alt"
                                    className="text-[12px] font-semibold text-muted-foreground"
                                >
                                    Texto alternativo (opcional)
                                </Label>
                                <Input
                                    id="gallery-alt"
                                    value={altText}
                                    maxLength={150}
                                    placeholder="Describe la foto"
                                    onChange={(e) => setAltText(e.target.value)}
                                    className="h-9 w-64 rounded-[10px]"
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-9 self-end rounded-[10px]"
                                disabled={uploading || remaining <= 0}
                                onClick={() => fileInput.current?.click()}
                            >
                                <Upload />
                                {uploading ? 'Subiendo…' : 'Elegir fotos'}
                            </Button>
                        </div>
                        <p className="text-[12.5px] text-muted-foreground">
                            JPG, PNG o WebP, hasta 5 MB cada una. Se guardan
                            reducidas a 1600 px en WebP.
                        </p>
                        {(localError || serverError) && (
                            <p className="text-sm text-bad">
                                {localError ?? serverError}
                            </p>
                        )}
                    </div>
                )}

                {active.length === 0 ? (
                    <p className="p-4 text-center text-sm text-muted-foreground">
                        Sin fotos activas. Sin foto, la tienda muestra un
                        marcador neutro.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        {active.map((image, index) => (
                            <div
                                key={image.id}
                                draggable={canEdit}
                                onDragStart={() => setDragIndex(index)}
                                onDragEnd={() => setDragIndex(null)}
                                onDragOver={(e) => e.preventDefault()}
                                onDrop={(e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    onCardDrop(index);
                                }}
                                className={cn(
                                    'group relative flex flex-col overflow-hidden rounded-[12px] border bg-card',
                                    canEdit && 'cursor-grab',
                                    dragIndex === index && 'opacity-50',
                                )}
                            >
                                <div className="relative aspect-square bg-muted">
                                    <img
                                        src={image.url}
                                        alt={image.alt_text ?? ''}
                                        className="size-full object-cover"
                                    />
                                    {index === 0 && (
                                        <span className="absolute top-2 left-2 inline-flex items-center gap-1 rounded-full bg-primary px-2 py-0.5 text-[11.5px] font-bold text-primary-foreground">
                                            <Star className="size-3" />
                                            Portada
                                        </span>
                                    )}
                                </div>
                                <div className="flex items-center justify-between gap-1 p-2">
                                    <span className="truncate text-[12px] text-muted-foreground">
                                        {image.alt_text ??
                                            `${image.width ?? '?'}×${image.height ?? '?'}`}
                                    </span>
                                    {canEdit && (
                                        <div className="flex shrink-0 items-center">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="size-7"
                                                aria-label="Subir"
                                                disabled={index === 0}
                                                onClick={() => move(index, -1)}
                                            >
                                                <ArrowUp className="size-3.5" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="size-7"
                                                aria-label="Bajar"
                                                disabled={
                                                    index === active.length - 1
                                                }
                                                onClick={() => move(index, 1)}
                                            >
                                                <ArrowDown className="size-3.5" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="size-7 text-bad"
                                                aria-label="Quitar"
                                                onClick={() =>
                                                    toggleStatus(image)
                                                }
                                            >
                                                <EyeOff className="size-3.5" />
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {inactive.length > 0 && (
                    <div className="flex flex-col gap-2">
                        <div className="text-[12.5px] font-semibold text-muted-foreground">
                            Fotos retiradas
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {inactive.map((image) => (
                                <div
                                    key={image.id}
                                    className="flex items-center gap-2 rounded-[10px] border p-1.5 opacity-70"
                                >
                                    <img
                                        src={image.url}
                                        alt={image.alt_text ?? ''}
                                        className="size-10 rounded-[6px] object-cover"
                                    />
                                    {canEdit && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="h-8 rounded-[8px]"
                                            onClick={() => toggleStatus(image)}
                                        >
                                            <Eye className="size-3.5" />
                                            Reactivar
                                        </Button>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </Card>
    );
}
