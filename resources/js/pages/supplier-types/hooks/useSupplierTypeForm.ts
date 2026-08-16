import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import supplierTypes from '@/routes/supplier-types';
import type { SupplierType } from '../types/SupplierType';

interface UseSupplierTypeFormProps {
    mode: 'create' | 'edit';
    initialData?: SupplierType;
    onSuccess?: () => void;
}

interface SupplierTypeFormData {
    id: string;
    name: string;
    description: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useSupplierTypeForm({
    mode,
    initialData,
    onSuccess,
}: UseSupplierTypeFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<SupplierTypeFormData>({
            id: initialData?.id ?? generateUUID(),
            name: initialData?.name ?? '',
            description: initialData?.description ?? '',
        });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(supplierTypes.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                supplierTypes.update({
                    company: companyId,
                    id: initialData.id,
                }).url,
                { onSuccess },
            );
        }
    };

    return { data, setData, processing, errors, handleSubmit, reset, mode };
}
