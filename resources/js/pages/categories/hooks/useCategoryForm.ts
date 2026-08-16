import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import categories from '@/routes/categories';
import type { Category } from '../types/Category';

interface UseCategoryFormProps {
    mode: 'create' | 'edit';
    initialData?: Category;
    onSuccess?: () => void;
}

interface CategoryFormData {
    id: string;
    name: string;
    description: string;
    order: number;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useCategoryForm({
    mode,
    initialData,
    onSuccess,
}: UseCategoryFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<CategoryFormData>({
            id: initialData?.id ?? generateUUID(),
            name: initialData?.name ?? '',
            description: initialData?.description ?? '',
            order: initialData?.order ?? 0,
        });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(categories.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                categories.update({ company: companyId, id: initialData.id })
                    .url,
                { onSuccess },
            );
        }
    };

    return { data, setData, processing, errors, handleSubmit, reset, mode };
}
