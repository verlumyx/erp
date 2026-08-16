import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import priceLists from '@/routes/price-lists';
import type { PriceList } from '../types/PriceList';

interface UsePriceListFormProps {
    mode: 'create' | 'edit';
    initialData?: PriceList;
    onSuccess?: () => void;
}

interface PriceListFormData {
    id: string;
    name: string;
    description: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function usePriceListForm({
    mode,
    initialData,
    onSuccess,
}: UsePriceListFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<PriceListFormData>({
            id: initialData?.id ?? generateUUID(),
            name: initialData?.name ?? '',
            description: initialData?.description ?? '',
        });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(priceLists.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                priceLists.update({ company: companyId, id: initialData.id })
                    .url,
                { onSuccess },
            );
        }
    };

    return { data, setData, processing, errors, handleSubmit, reset, mode };
}
