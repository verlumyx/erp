import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import measurementUnits from '@/routes/measurement-units';
import type { MeasurementUnit } from '../types/MeasurementUnit';

interface UseMeasurementUnitFormProps {
    mode: 'create' | 'edit';
    initialData?: MeasurementUnit;
    onSuccess?: () => void;
}

interface MeasurementUnitFormData {
    id: string;
    name: string;
    abbreviation: string;
    description: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useMeasurementUnitForm({
    mode,
    initialData,
    onSuccess,
}: UseMeasurementUnitFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<MeasurementUnitFormData>({
            id: initialData?.id ?? generateUUID(),
            name: initialData?.name ?? '',
            abbreviation: initialData?.abbreviation ?? '',
            description: initialData?.description ?? '',
        });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(measurementUnits.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                measurementUnits.update({
                    company: companyId,
                    id: initialData.id,
                }).url,
                { onSuccess },
            );
        }
    };

    return { data, setData, processing, errors, handleSubmit, reset, mode };
}
