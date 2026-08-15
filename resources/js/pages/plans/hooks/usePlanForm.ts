import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import plans from '@/routes/plans';
import type { Plan, PlanCapacity, PlanServiceOption } from '../types/Plan';

interface UsePlanFormProps {
    mode: 'create' | 'edit';
    services: PlanServiceOption[];
    initialData?: Plan;
    onSuccess?: () => void;
}

interface PlanFormData {
    id: string;
    service_id: string;
    name: string;
    capacity: PlanCapacity;
    duration_days: number;
    sale_price: number;
    roi_target_pct: number;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function usePlanForm({
    mode,
    services,
    initialData,
    onSuccess,
}: UsePlanFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<PlanFormData>({
            id: initialData?.id ?? generateUUID(),
            service_id: initialData?.service_id ?? services[0]?.id ?? '',
            name: initialData?.name ?? '',
            capacity: initialData?.capacity ?? 'profile',
            duration_days: initialData?.duration_days ?? 30,
            sale_price: initialData ? Number(initialData.sale_price) : 0,
            roi_target_pct: initialData ? Number(initialData.roi_target_pct) : 0,
        });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(plans.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(plans.update({ company: companyId, id: initialData.id }).url, {
                onSuccess,
            });
        }
    };

    return { data, setData, processing, errors, handleSubmit, reset, mode, services };
}
