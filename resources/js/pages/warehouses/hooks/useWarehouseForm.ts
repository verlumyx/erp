import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import warehouses from '@/routes/warehouses';
import type {
    Warehouse,
    WarehouseType,
    WarehouseUserOption,
    YesNo,
} from '../types/Warehouse';

interface UseWarehouseFormProps {
    mode: 'create' | 'edit';
    users: WarehouseUserOption[];
    initialData?: Warehouse;
    onSuccess?: () => void;
}

interface WarehouseFormData {
    id: string;
    name: string;
    type: WarehouseType;
    address: string;
    phone: string;
    city: string;
    responsible_user_id: string;
    is_default: YesNo;
    allows_negative_stock: YesNo;
    uses_locations: YesNo;
    is_sales_available: YesNo;
    notes: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useWarehouseForm({
    mode,
    users,
    initialData,
    onSuccess,
}: UseWarehouseFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<WarehouseFormData>({
            id: initialData?.id ?? generateUUID(),
            name: initialData?.name ?? '',
            type: initialData?.type ?? 'main',
            address: initialData?.address ?? '',
            phone: initialData?.phone ?? '',
            city: initialData?.city ?? '',
            responsible_user_id: initialData?.responsible_user_id ?? '',
            is_default: initialData?.is_default ?? 'no',
            allows_negative_stock: initialData?.allows_negative_stock ?? 'no',
            uses_locations: initialData?.uses_locations ?? 'no',
            is_sales_available: initialData?.is_sales_available ?? 'yes',
            notes: initialData?.notes ?? '',
        });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(warehouses.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                warehouses.update({ company: companyId, id: initialData.id })
                    .url,
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
        users,
    };
}
