import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import taxes from '@/routes/taxes';
import type { Tax, YesNo } from '../types/Tax';

interface UseTaxFormProps {
    mode: 'create' | 'edit';
    initialData?: Tax;
    onSuccess?: () => void;
}

interface TaxFormData {
    id: string;
    name: string;
    description: string;
    percentage: number;
    has_withholding: YesNo;
    withholding_percentage: number;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useTaxForm({ mode, initialData, onSuccess }: UseTaxFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<TaxFormData>({
            id: initialData?.id ?? generateUUID(),
            name: initialData?.name ?? '',
            description: initialData?.description ?? '',
            percentage: Number(initialData?.percentage ?? 0),
            has_withholding: initialData?.has_withholding ?? 'no',
            withholding_percentage: Number(
                initialData?.withholding_percentage ?? 0,
            ),
        });

    /**
     * Sin retención el porcentaje de retención se fuerza a 0, igual que en el
     * backend, para que el resumen del formulario no muestre un valor muerto.
     */
    const setHasWithholding = (value: YesNo) => {
        setData((current) => ({
            ...current,
            has_withholding: value,
            withholding_percentage:
                value === 'yes' ? current.withholding_percentage : 0,
        }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(taxes.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(taxes.update({ company: companyId, id: initialData.id }).url, {
                onSuccess,
            });
        }
    };

    return {
        data,
        setData,
        setHasWithholding,
        processing,
        errors,
        handleSubmit,
        reset,
        mode,
    };
}
