import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import warehouseLocations from '@/routes/warehouse-locations';
import type {
    ParentOption,
    WarehouseLocation,
    WarehouseLocationType,
    WarehouseOption,
    YesNo,
} from '../types/WarehouseLocation';

interface UseWarehouseLocationFormProps {
    mode: 'create' | 'edit';
    warehouses: WarehouseOption[];
    parents: ParentOption[];
    initialData?: WarehouseLocation;
    onSuccess?: () => void;
}

interface WarehouseLocationFormData {
    id: string;
    warehouse_id: string;
    parent_id: string;
    name: string;
    location_code: string;
    type: WarehouseLocationType;
    capacity: number;
    is_default: YesNo;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useWarehouseLocationForm({
    mode,
    warehouses,
    parents,
    initialData,
    onSuccess,
}: UseWarehouseLocationFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<WarehouseLocationFormData>({
            id: initialData?.id ?? generateUUID(),
            warehouse_id: initialData?.warehouse_id ?? '',
            parent_id: initialData?.parent_id ?? '',
            name: initialData?.name ?? '',
            location_code: initialData?.location_code ?? '',
            type: initialData?.type ?? 'shelf',
            capacity: initialData?.capacity ?? 0,
            is_default: initialData?.is_default ?? 'no',
        });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(warehouseLocations.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                warehouseLocations.update({
                    company: companyId,
                    id: initialData.id,
                }).url,
                { onSuccess },
            );
        }
    };

    return {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        reset,
        mode,
        warehouses,
        parents,
    };
}
