import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import clientTypes from '@/routes/client-types';
import type { ClientType } from '../types/ClientType';

interface UseClientTypeFormProps {
    mode: 'create' | 'edit';
    initialData?: ClientType;
    onSuccess?: () => void;
}

interface ClientTypeFormData {
    id: string;
    name: string;
    description: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useClientTypeForm({
    mode,
    initialData,
    onSuccess,
}: UseClientTypeFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<ClientTypeFormData>({
            id: initialData?.id ?? generateUUID(),
            name: initialData?.name ?? '',
            description: initialData?.description ?? '',
        });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(clientTypes.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                clientTypes.update({
                    company: companyId,
                    id: initialData.id,
                }).url,
                { onSuccess },
            );
        }
    };

    return { data, setData, processing, errors, handleSubmit, reset, mode };
}
