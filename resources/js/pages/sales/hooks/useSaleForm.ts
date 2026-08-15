import { useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { generateUUID } from '@/lib/utils';
import sales from '@/routes/sales';
import type { AvailableProfile, PlanOption } from '../types/Sale';

export interface SaleFormData {
    id: string;
    client_id: string;
    plan_id: string;
    start_date: string;
    profile_ids: string[];
    [key: string]: string | string[];
}

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

/**
 * Estado y lógica del wizard de creación de venta (3 pasos). Centraliza el
 * formulario de Inertia, el paso actual y los datos derivados del plan elegido
 * (servicio, profiles requeridos y disponibles del servicio).
 */
export function useSaleForm(
    companyId: string,
    plans: PlanOption[],
    availableProfiles: AvailableProfile[],
    initialClientId = '',
) {
    const form = useForm<SaleFormData>({
        id: generateUUID(),
        client_id: initialClientId,
        plan_id: '',
        start_date: today(),
        profile_ids: [],
    });

    const [step, setStep] = useState(1);

    const selectedPlan = useMemo(
        () => plans.find((p) => p.id === form.data.plan_id) ?? null,
        [plans, form.data.plan_id],
    );

    const requiredCount = useMemo(() => {
        if (selectedPlan === null) {
            return 0;
        }
        return selectedPlan.capacity === 'full_account'
            ? selectedPlan.max_profiles
            : 1;
    }, [selectedPlan]);

    const serviceProfiles = useMemo(() => {
        if (selectedPlan === null) {
            return [];
        }
        return availableProfiles.filter(
            (p) => p.service_id === selectedPlan.service_id,
        );
    }, [availableProfiles, selectedPlan]);

    const selectClient = (clientId: string) => {
        form.setData('client_id', clientId);
    };

    const selectPlan = (planId: string) => {
        form.setData((prev) => ({
            ...prev,
            plan_id: planId,
            profile_ids: [],
        }));
    };

    const toggleProfile = (profileId: string) => {
        form.setData((prev) => {
            const exists = prev.profile_ids.includes(profileId);
            const next = exists
                ? prev.profile_ids.filter((id) => id !== profileId)
                : [...prev.profile_ids, profileId];
            return { ...prev, profile_ids: next };
        });
    };

    const canContinue = useMemo(() => {
        if (step === 1) {
            return form.data.client_id !== '';
        }
        if (step === 2) {
            return form.data.plan_id !== '' && form.data.start_date !== '';
        }
        return form.data.profile_ids.length === requiredCount;
    }, [step, form.data, requiredCount]);

    const submit = () => {
        form.post(sales.store(companyId).url, { preserveScroll: true });
    };

    return {
        form,
        step,
        setStep,
        selectedPlan,
        requiredCount,
        serviceProfiles,
        selectClient,
        selectPlan,
        toggleProfile,
        canContinue,
        submit,
    };
}

export type SaleFormState = ReturnType<typeof useSaleForm>;
