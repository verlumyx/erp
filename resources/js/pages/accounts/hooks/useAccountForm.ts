import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import accounts from '@/routes/accounts';
import type {
    Account,
    AccountStatus,
    AccountServiceOption,
    ProfileStatus,
} from '../types/Account';

interface UseAccountFormProps {
    mode: 'create' | 'edit';
    services: AccountServiceOption[];
    initialData?: Account;
    onSuccess?: () => void;
}

export interface ProfileRow {
    number: number;
    pin: string;
    status: ProfileStatus;
    notes: string;
}

interface AccountFormData {
    id: string;
    service_id: string;
    email: string;
    password: string;
    cost: number;
    purchase_date: string;
    next_renewal: string;
    status: AccountStatus;
    notes: string;
    profiles: ProfileRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function isoDate(date: Date): string {
    return date.toISOString().slice(0, 10);
}

/** Crea N filas de profile vacías (1..max), en status available. */
function blankRows(max: number): ProfileRow[] {
    return Array.from({ length: Math.max(0, max) }, (_, i) => ({
        number: i + 1,
        pin: '',
        status: 'available' as ProfileStatus,
        notes: '',
    }));
}

export function useAccountForm({
    mode,
    services,
    initialData,
    onSuccess,
}: UseAccountFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const initialServiceId = initialData?.service_id ?? services[0]?.id ?? '';

    const initialRows: ProfileRow[] = initialData?.profiles
        ? initialData.profiles
              .slice()
              .sort((a, b) => a.number - b.number)
              .map((p) => ({
                  number: p.number,
                  pin: p.pin ?? '',
                  status: p.status,
                  notes: p.notes ?? '',
              }))
        : blankRows(
              services.find((s) => s.id === initialServiceId)?.max_profiles ??
                  0,
          );

    const today = new Date();
    const in30 = new Date();
    in30.setDate(in30.getDate() + 30);

    const form = useForm<AccountFormData>({
        id: initialData?.id ?? generateUUID(),
        service_id: initialServiceId,
        email: initialData?.email ?? '',
        password: '',
        cost: initialData ? Number(initialData.cost) : 0,
        purchase_date: initialData?.purchase_date ?? isoDate(today),
        next_renewal: initialData?.next_renewal ?? isoDate(in30),
        status: initialData?.status ?? 'active',
        notes: initialData?.notes ?? '',
        profiles: initialRows,
    });

    const { data, setData, post, put, processing, errors, reset } = form;

    const selectedService = services.find((s) => s.id === data.service_id);

    /** Al cambiar el servicio (solo en creación) se regeneran las filas de perfil. */
    const changeService = (serviceId: string) => {
        const max = services.find((s) => s.id === serviceId)?.max_profiles ?? 0;
        setData((prev) => ({
            ...prev,
            service_id: serviceId,
            profiles: blankRows(max),
        }));
    };

    const setProfile = <K extends keyof ProfileRow>(
        index: number,
        field: K,
        value: ProfileRow[K],
    ) => {
        setData(
            'profiles',
            data.profiles.map((row, i) =>
                i === index ? { ...row, [field]: value } : row,
            ),
        );
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            form.transform((payload) => ({
                ...payload,
                profiles: (payload.profiles as ProfileRow[]).map((row) => ({
                    number: row.number,
                    pin: row.pin.trim() === '' ? null : row.pin.trim(),
                })),
            }));
            post(accounts.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            form.transform((payload) => {
                const { password, ...rest } = payload as AccountFormData;
                return {
                    ...rest,
                    ...(password.trim() !== '' ? { password } : {}),
                    profiles: (payload.profiles as ProfileRow[]).map((row) => ({
                        number: row.number,
                        pin: row.pin.trim() === '' ? null : row.pin.trim(),
                        status: row.status,
                        notes:
                            row.notes.trim() === '' ? null : row.notes.trim(),
                    })),
                };
            });
            put(
                accounts.update({ company: companyId, id: initialData.id }).url,
                {
                    onSuccess,
                },
            );
        }
    };

    return {
        data,
        setData,
        setProfile,
        changeService,
        processing,
        errors,
        handleSubmit,
        reset,
        mode,
        services,
        selectedService,
    };
}
