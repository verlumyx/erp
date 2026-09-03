import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import { generateUUID } from '@/lib/utils';
import storeItems from '@/routes/store-items';
import type { StoreItem, YesNo } from '../types/Store';

interface UseStoreItemFormProps {
    mode: 'create' | 'edit';
    initialData?: StoreItem;
}

export interface StoreItemFormData {
    id: string;
    item_id: string;
    title: string;
    slug: string;
    summary: string;
    description: string;
    is_featured: YesNo;
    order: number;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/** Lo que el select remoto de artículos trae en `meta`. */
interface PublishableItemMeta {
    code?: string;
    sku?: string | null;
    name?: string;
    description?: string | null;
    category_name?: string | null;
}

export function useStoreItemForm({ mode, initialData }: UseStoreItemFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, transform } =
        useForm<StoreItemFormData>({
            id: initialData?.id ?? generateUUID(),
            item_id: initialData?.item_id ?? '',
            title: initialData?.title ?? '',
            slug: initialData?.slug ?? '',
            summary: initialData?.summary ?? '',
            description: initialData?.description ?? '',
            is_featured: initialData?.is_featured ?? 'no',
            order: initialData?.order ?? 0,
        });

    /** El artículo elegido en el select remoto; al editar no cambia. */
    const [itemOption, setItemOption] = useState<AjaxOption | null>(null);

    /**
     * Elegir el artículo precarga el título y la descripción con lo que ya
     * dice el maestro; el usuario los adapta después.
     */
    const selectItem = (option: AjaxOption | null) => {
        setItemOption(option);
        const meta = (option?.meta ?? {}) as PublishableItemMeta;

        setData((current) => ({
            ...current,
            item_id: option?.value ?? '',
            title: option ? (meta.name ?? current.title) : current.title,
            description: option
                ? (meta.description ?? current.description)
                : current.description,
        }));
    };

    const lookupUrl = storeItems.lookup(companyId).url;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(storeItems.store(companyId).url);

            return;
        }

        /** Al editar, el artículo no viaja: no se puede cambiar. */
        transform((current) => {
            const { item_id: _itemId, ...rest } = current;
            void _itemId;

            return rest;
        });

        put(storeItems.update({ company: companyId, id: data.id }).url, {
            preserveScroll: true,
        });
    };

    return {
        mode,
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        itemOption,
        selectItem,
        lookupUrl,
        storeItem: initialData ?? null,
    };
}
