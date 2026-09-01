import { useForm, usePage } from '@inertiajs/react';
import configuration from '@/routes/configuration';
import type { Configuration } from '@/types';

interface UseConfigurationFormProps {
    initialData: Configuration;
    onSuccess?: () => void;
}

interface ConfigurationFormData {
    base_currency: string;
    secondary_currency: string;
    rate_type: 'legal' | 'manual';
    allows_rate_override: 'yes' | 'no';
    amount_decimals: number;
    price_decimals: number;
    adjustment_approval_threshold: number;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useConfigurationForm({
    initialData,
    onSuccess,
}: UseConfigurationFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, put, processing, errors, recentlySuccessful } =
        useForm<ConfigurationFormData>({
            base_currency: initialData.base_currency,
            secondary_currency: initialData.secondary_currency ?? '',
            rate_type: initialData.rate_type,
            allows_rate_override: initialData.allows_rate_override,
            amount_decimals: initialData.amount_decimals,
            price_decimals: initialData.price_decimals,
            adjustment_approval_threshold: Number(
                initialData.adjustment_approval_threshold ?? 0,
            ),
        });

    /**
     * Con la misma moneda a ambos lados no hay conversión: la vista previa y
     * los importes de todo el sistema pasan a una sola línea.
     */
    const usesDualCurrency =
        data.secondary_currency !== '' &&
        data.secondary_currency !== data.base_currency;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        put(configuration.update(companyId).url, {
            preserveScroll: true,
            onSuccess,
        });
    };

    return {
        data,
        setData,
        processing,
        errors,
        recentlySuccessful,
        usesDualCurrency,
        handleSubmit,
    };
}
