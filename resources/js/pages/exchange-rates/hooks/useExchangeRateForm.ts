import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import exchangeRates from '@/routes/exchange-rates';
import type { ExchangeRate } from '../types/ExchangeRate';

interface UseExchangeRateFormProps {
    mode: 'create' | 'edit';
    initialData?: ExchangeRate;
    onSuccess?: () => void;
}

interface ExchangeRateFormData {
    id: string;
    currency: string;
    rate_date: string;
    rate: string;
    type: string;
    source: string;
    description: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

export function useExchangeRateForm({
    mode,
    initialData,
    onSuccess,
}: UseExchangeRateFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<ExchangeRateFormData>({
            id: initialData?.id ?? generateUUID(),
            currency: initialData?.currency ?? 'USD',
            rate_date: initialData?.rate_date ?? today(),
            rate: initialData?.rate ?? '',
            type: initialData?.type ?? 'legal',
            source: initialData?.source ?? '',
            description: initialData?.description ?? '',
        });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(exchangeRates.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                exchangeRates.update({
                    company: companyId,
                    id: initialData.id,
                }).url,
                { onSuccess },
            );
        }
    };

    return { data, setData, processing, errors, handleSubmit, reset, mode };
}
