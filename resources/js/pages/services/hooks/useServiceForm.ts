import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import services from '@/routes/services';
import type { Service } from '../types/Service';

interface UseServiceFormProps {
    mode: 'create' | 'edit';
    initialData?: Service;
    onSuccess?: () => void;
}

interface ServiceFormData {
    id: string;
    name: string;
    logo_url: string;
    max_profiles: number;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useServiceForm({
    mode,
    initialData,
    onSuccess,
}: UseServiceFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<ServiceFormData>({
            id: initialData?.id ?? generateUUID(),
            name: initialData?.name ?? '',
            logo_url: initialData?.logo_url ?? '',
            max_profiles: initialData?.max_profiles ?? 1,
        });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(services.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                services.update({ company: companyId, id: initialData.id }).url,
                { onSuccess },
            );
        }
    };

    return { data, setData, processing, errors, handleSubmit, reset, mode };
}
