import { useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import loadPlans from '@/routes/load-plans';
import type {
    LoadPlan,
    LoadPlanDispatchItem,
    LoadPlanFormData,
    LoadPlanOptions,
} from '../types/LoadPlan';

interface UseLoadPlanFormProps {
    mode: 'create' | 'edit';
    initialData?: LoadPlan;
    options: LoadPlanOptions;
    onSuccess?: () => void;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useLoadPlanForm({
    mode,
    initialData,
    options,
    onSuccess,
}: UseLoadPlanFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const today = new Date().toISOString().slice(0, 10);

    const initialDispatches: LoadPlanDispatchItem[] = useMemo(() => {
        if (!initialData?.dispatches) {
            return [];
        }
        return initialData.dispatches.map((d, index) => ({
            id: d.id,
            dispatch_id: d.dispatch_id,
            sequence: d.sequence || index + 1,
            status: d.status,
            dispatch_code: d.dispatch_code,
            dispatch_date: d.dispatch_date,
            recipient_name: d.recipient_name,
            recipient_code: d.recipient_code,
            address: d.address,
            order_code: d.order_code,
            invoice_code: d.invoice_code,
            total_quantity: d.total_quantity,
            total_weight: d.total_weight,
            total_volume: d.total_volume,
            dispatch_status: d.dispatch_status,
        }));
    }, [initialData]);

    const { data, setData, post, put, processing, errors, reset } =
        useForm<LoadPlanFormData>({
            id: initialData?.id ?? '',
            plan_date: initialData?.plan_date ?? today,
            route_id: initialData?.route_id ?? '',
            warehouse_id: initialData?.warehouse_id ?? '',
            vehicle_id: initialData?.vehicle_id ?? '',
            driver_name: initialData?.driver_name ?? '',
            driver_id: initialData?.driver_id ?? '',
            helper_name: initialData?.helper_name ?? '',
            description: initialData?.description ?? '',
            dispatches: initialDispatches,
        });

    const currentVehicle = useMemo(() => {
        return options.vehicles.find((v) => v.id === data.vehicle_id) ?? null;
    }, [options.vehicles, data.vehicle_id]);

    const totalWeight = useMemo(() => {
        return data.dispatches.reduce(
            (acc, curr) => acc + (Number(curr.total_weight) || 0),
            0,
        );
    }, [data.dispatches]);

    const totalVolume = useMemo(() => {
        return data.dispatches.reduce(
            (acc, curr) => acc + (Number(curr.total_volume) || 0),
            0,
        );
    }, [data.dispatches]);

    const totalDispatches = data.dispatches.length;

    const weightPercentage = useMemo(() => {
        if (!currentVehicle || !currentVehicle.capacity_weight) return 0;
        return Math.round((totalWeight / currentVehicle.capacity_weight) * 100);
    }, [currentVehicle, totalWeight]);

    const volumePercentage = useMemo(() => {
        if (!currentVehicle || !currentVehicle.capacity_volume) return 0;
        return Math.round((totalVolume / currentVehicle.capacity_volume) * 100);
    }, [currentVehicle, totalVolume]);

    const addDispatches = (newItems: LoadPlanDispatchItem[]) => {
        const existingIds = new Set(data.dispatches.map((d) => d.dispatch_id));
        const filteredNew = newItems.filter(
            (item) => !existingIds.has(item.dispatch_id),
        );

        if (filteredNew.length === 0) return;

        const currentCount = data.dispatches.length;
        const mapped = filteredNew.map((item, idx) => ({
            ...item,
            sequence: currentCount + idx + 1,
        }));

        setData('dispatches', [...data.dispatches, ...mapped]);
    };

    const removeDispatch = (index: number) => {
        const next = data.dispatches.filter((_, idx) => idx !== index);
        const resequenced = next.map((item, idx) => ({
            ...item,
            sequence: idx + 1,
        }));
        setData('dispatches', resequenced);
    };

    const moveDispatch = (index: number, direction: -1 | 1) => {
        const targetIndex = index + direction;
        if (targetIndex < 0 || targetIndex >= data.dispatches.length) return;

        const updated = [...data.dispatches];
        const temp = updated[index];
        updated[index] = updated[targetIndex];
        updated[targetIndex] = temp;

        const resequenced = updated.map((item, idx) => ({
            ...item,
            sequence: idx + 1,
        }));

        setData('dispatches', resequenced);
    };

    const handleRouteChange = (routeId: string) => {
        setData('route_id', routeId);
        const selectedRoute = options.routes.find((r) => r.id === routeId);
        if (selectedRoute) {
            if (!data.warehouse_id && selectedRoute.warehouse_id) {
                setData('warehouse_id', selectedRoute.warehouse_id);
            }
            if (!data.driver_name && selectedRoute.driver_name) {
                setData('driver_name', selectedRoute.driver_name);
            }
            if (!data.driver_id && selectedRoute.driver_id) {
                setData('driver_id', selectedRoute.driver_id);
            }
            if (!data.vehicle_id && selectedRoute.vehicle_plate) {
                const matchedVehicle = options.vehicles.find(
                    (v) =>
                        v.plate.toLowerCase() ===
                        selectedRoute.vehicle_plate?.toLowerCase(),
                );
                if (matchedVehicle) {
                    setData('vehicle_id', matchedVehicle.id);
                }
            }
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(loadPlans.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (initialData?.id) {
            put(
                loadPlans.update({
                    company: companyId,
                    id: initialData.id,
                }).url,
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
        options,
        currentVehicle,
        totalWeight,
        totalVolume,
        totalDispatches,
        weightPercentage,
        volumePercentage,
        addDispatches,
        removeDispatch,
        moveDispatch,
        handleRouteChange,
        companyId,
    };
}
