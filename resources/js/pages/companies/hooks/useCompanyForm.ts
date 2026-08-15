import { useForm, usePage } from '@inertiajs/react';
import { Company } from '../types/Company';
import companies from '@/routes/companies';
import { generateUUID } from '@/lib/utils';

interface UseCompanyFormProps {
    mode: 'create' | 'edit';
    initialData?: Company;
    onSuccess?: () => void;
}

interface CompanyFormData {
    id: string;
    name: string;
    description: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useCompanyForm({ mode, initialData, onSuccess }: UseCompanyFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } = useForm<CompanyFormData>({
        id: initialData?.id ?? generateUUID(),
        name: initialData?.name ?? '',
        description: initialData?.description ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(companies.store(companyId).url, { onSuccess: () => { reset(); onSuccess?.(); } });
        } else if (mode === 'edit' && initialData) {
            put(companies.update({ company: companyId, id: initialData.id }).url, { onSuccess });
        }
    };

    return { data, setData, processing, errors, handleSubmit, reset };
}
