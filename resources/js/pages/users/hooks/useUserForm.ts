import { useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import users from '@/routes/users';
import { generateUUID } from '@/lib/utils';
import { ExistingUser, UserFormData } from '../contexts/UserFormContext';

interface User {
    id: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    role?: {
        id: string;
        name: string;
    } | null;
    company?: {
        id: string;
        name: string;
    } | null;
}

interface UseUserFormProps {
    mode: 'create' | 'edit';
    initialData?: User;
    onSuccess?: () => void;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useUserForm({
    mode,
    initialData,
    onSuccess,
}: UseUserFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [emailStep, setEmailStep] = useState(mode === 'create');
    const [emailChecked, setEmailChecked] = useState(mode === 'edit');
    const [existingUser, setExistingUser] = useState<ExistingUser | null>(null);
    const [isCheckingEmail, setIsCheckingEmail] = useState(false);
    const [alreadyInCompany, setAlreadyInCompany] = useState(false);

    const { data, setData: inertiaSetData, post, put, processing, errors, reset } = useForm<UserFormData>({
        id: initialData?.id ?? generateUUID(),
        name: initialData?.name || '',
        email: initialData?.email || '',
        password: '',
        password_confirmation: '',
        role_id: initialData?.role?.id || '',
        existing_user_id: '',
    });

    const setData = (key: keyof UserFormData, value: string) => {
        inertiaSetData(key, value);
    };

    const handleEmailCheck = async (email: string) => {
        setIsCheckingEmail(true);
        setAlreadyInCompany(false);

        try {
            const params = new URLSearchParams({ email });
            const response = await fetch(`/${companyId}/users/check-email?${params}`, {
                headers: { Accept: 'application/json' },
            });

            const payload = (await response.json()) as {
                exists: boolean;
                already_in_company: boolean;
                user: ExistingUser | null;
            };

            if (payload.already_in_company) {
                setAlreadyInCompany(true);
                return;
            }

            if (payload.exists && payload.user) {
                setExistingUser(payload.user);
                inertiaSetData((prev) => ({
                    ...prev,
                    name: payload.user!.name,
                    existing_user_id: payload.user!.id,
                }));
            } else {
                setExistingUser(null);
                inertiaSetData((prev) => ({
                    ...prev,
                    existing_user_id: '',
                }));
            }

            setEmailChecked(true);
            setEmailStep(false);
        } finally {
            setIsCheckingEmail(false);
        }
    };

    const handleChangeEmail = () => {
        setEmailStep(true);
        setEmailChecked(false);
        setExistingUser(null);
        setAlreadyInCompany(false);
        inertiaSetData((prev) => ({
            ...prev,
            existing_user_id: '',
            name: '',
            password: '',
            password_confirmation: '',
        }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(users.store(companyId).url, { onSuccess });
        } else if (mode === 'edit' && initialData) {
            put(users.update({ company: companyId, id: initialData.id }).url, { onSuccess });
        }
    };

    return {
        data,
        setData,
        errors,
        processing,
        handleSubmit,
        reset,
        emailStep,
        emailChecked,
        existingUser,
        isCheckingEmail,
        alreadyInCompany,
        handleEmailCheck,
        handleChangeEmail,
    };
}
