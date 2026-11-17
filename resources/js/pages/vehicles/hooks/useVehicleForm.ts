import { useForm, usePage } from '@inertiajs/react';
import vehicles from '@/routes/vehicles';
import type { Vehicle, VehicleFormData, VehicleType } from '../types/Vehicle';

interface UseVehicleFormProps {
    mode: 'create' | 'edit';
    initialData?: Vehicle;
    onSuccess?: () => void;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useVehicleForm({
    mode,
    initialData,
    onSuccess,
}: UseVehicleFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<VehicleFormData>({
            id: initialData?.id ?? '',
            plate: initialData?.plate ?? '',
            name: initialData?.name ?? '',
            brand: initialData?.brand ?? '',
            model: initialData?.model ?? '',
            year: initialData?.year ?? '',
            vehicle_type: (initialData?.vehicle_type as VehicleType) ?? 'truck',
            capacity_weight: initialData?.capacity_weight ?? 0,
            capacity_volume: initialData?.capacity_volume ?? 0,
            notes: initialData?.notes ?? '',
        });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(vehicles.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (initialData?.id) {
            put(
                vehicles.update({ company: companyId, id: initialData.id }).url,
                {
                    onSuccess: () => {
                        onSuccess?.();
                    },
                },
            );
        }
    };

    return {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        companyId,
    };
}
