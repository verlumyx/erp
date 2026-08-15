import { useForm, usePage } from '@inertiajs/react';
import { joinPhone, splitPhone } from '@/lib/phone';
import { generateUUID } from '@/lib/utils';
import clients from '@/routes/clients';
import type { Client } from '../types/Client';

interface UseClientFormProps {
    mode: 'create' | 'edit';
    initialData?: Client;
    onSuccess?: () => void;
}

interface ClientFormData {
    id: string;
    name: string;
    phone_prefix: string;
    phone: string;
    email: string;
    notes: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useClientForm({
    mode,
    initialData,
    onSuccess,
}: UseClientFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const initialPhone = splitPhone(initialData?.phone);

    const { data, setData, transform, post, put, processing, errors, reset } =
        useForm<ClientFormData>({
            id: initialData?.id ?? generateUUID(),
            name: initialData?.name ?? '',
            phone_prefix: initialPhone.prefix,
            phone: initialPhone.number,
            email: initialData?.email ?? '',
            notes: initialData?.notes ?? '',
        });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        transform(({ phone_prefix, phone, ...rest }) => ({
            ...rest,
            phone: joinPhone(phone_prefix, phone),
        }));

        if (mode === 'create') {
            post(clients.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                clients.update({ company: companyId, id: initialData.id }).url,
                { onSuccess },
            );
        }
    };

    return { data, setData, processing, errors, handleSubmit, reset, mode };
}
