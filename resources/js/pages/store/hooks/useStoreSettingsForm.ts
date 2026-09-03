import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import storeSettings from '@/routes/store-settings';
import type { StoreSetting, YesNo } from '../types/Store';

interface UseStoreSettingsFormProps {
    initialData: StoreSetting;
}

export interface StoreSettingsFormData {
    is_enabled: YesNo;
    store_name: string;
    brand_color: string;
    price_list_id: string;
    warehouse_id: string;
    shows_stock: YesNo;
    allows_orders: YesNo;
    default_client_type_id: string;
    shows_secondary_currency: YesNo;
    contact_phone: string;
    contact_email: string;
    store_url: string;
    logo: File | null;
    remove_logo: YesNo;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function useStoreSettingsForm({
    initialData,
}: UseStoreSettingsFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const {
        data,
        setData,
        put,
        post,
        processing,
        errors,
        recentlySuccessful,
        transform,
    } = useForm<StoreSettingsFormData>({
        is_enabled: initialData.is_enabled,
        store_name: initialData.store_name,
        brand_color: initialData.brand_color,
        price_list_id: initialData.price_list_id ?? '',
        warehouse_id: initialData.warehouse_id ?? '',
        shows_stock: initialData.shows_stock,
        allows_orders: initialData.allows_orders,
        default_client_type_id: initialData.default_client_type_id ?? '',
        shows_secondary_currency: initialData.shows_secondary_currency,
        contact_phone: initialData.contact_phone ?? '',
        contact_email: initialData.contact_email ?? '',
        store_url: initialData.store_url ?? '',
        logo: null,
        remove_logo: 'no',
    });

    /** Vista previa local del logo elegido, antes de guardar. */
    const [logoPreview, setLogoPreview] = useState<string | null>(null);

    const selectLogo = (file: File | null) => {
        setData((current) => ({ ...current, logo: file, remove_logo: 'no' }));
        setLogoPreview(file ? URL.createObjectURL(file) : null);
    };

    const removeLogo = () => {
        setData((current) => ({ ...current, logo: null, remove_logo: 'yes' }));
        setLogoPreview(null);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const url = storeSettings.update(companyId).url;

        /**
         * Con un archivo el envío va como `multipart/form-data`, que no admite
         * PUT: se manda por POST con `_method=put`, como hace Inertia por
         * dentro.
         */
        if (data.logo instanceof File) {
            transform((current) => ({ ...current, _method: 'put' }));
            post(url, {
                preserveScroll: true,
                forceFormData: true,
                onSuccess: () => {
                    setLogoPreview(null);
                    setData((current) => ({ ...current, logo: null }));
                },
            });

            return;
        }

        transform((current) => {
            const { logo: _logo, ...rest } = current;
            void _logo;

            return rest;
        });
        put(url, { preserveScroll: true });
    };

    /** Genera otra llave: la anterior queda invalidada de inmediato. */
    const generateKey = () => {
        router.post(
            storeSettings.generateKey(companyId).url,
            {},
            { preserveScroll: true },
        );
    };

    const currentLogoUrl =
        data.remove_logo === 'yes'
            ? null
            : (logoPreview ?? initialData.logo_url);

    return {
        data,
        setData,
        processing,
        errors,
        recentlySuccessful,
        handleSubmit,
        generateKey,
        selectLogo,
        removeLogo,
        currentLogoUrl,
        settings: initialData,
    };
}
