import { useForm, usePage } from '@inertiajs/react';
import roles from '@/routes/roles';
import { generateUUID } from '@/lib/utils';

interface Role {
    id: string;
    name: string;
    status: 'active' | 'inactive';
    description: string;
    permission_type: 'all' | 'custom';
    permissions?: string[];
    created_at: string;
    updated_at: string | null;
}

interface UseRoleFormProps {
    mode: 'create' | 'edit';
    initialData?: Role;
    onSuccess?: () => void;
}

interface RoleFormData {
    id: string;
    name: string;
    description: string;
    permission_type: 'all' | 'custom';
    permissions: string[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useRoleForm({
    mode,
    initialData,
    onSuccess
}: UseRoleFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } = useForm<RoleFormData>({
        id: initialData?.id ?? generateUUID(),
        name: initialData?.name || '',
        description: initialData?.description || '',
        permission_type: initialData?.permission_type || 'custom',
        permissions: initialData?.permissions || [],
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(roles.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(roles.update({ company: companyId, id: initialData.id }).url, {
                onSuccess: () => {
                    onSuccess?.();
                },
            });
        }
    };

    return {
        data,
        setData,
        errors,
        processing,
        handleSubmit,
        reset,
    };
}
