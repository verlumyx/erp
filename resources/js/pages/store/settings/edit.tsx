import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import storeSettings from '@/routes/store-settings';
import type { BreadcrumbItem } from '@/types';
import { StoreApiKeyDialog } from '../components/StoreApiKeyDialog';
import { StoreSettingsForm } from '../components/StoreSettingsForm';
import { StoreSettingsFormProvider } from '../contexts/StoreSettingsFormContext';
import { useStoreSettingsForm } from '../hooks/useStoreSettingsForm';
import type { StoreSetting, StoreSettingOptions } from '../types/Store';

interface Props {
    settings: StoreSetting;
    options: StoreSettingOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    flash?: { store_api_key?: string | null };
    [key: string]: unknown;
}

export default function StoreSettingsEdit({ settings, options }: Props) {
    const { currentCompany, flash } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    /**
     * La llave llega una sola vez por flash. El diálogo se muestra mientras
     * el usuario no la haya descartado: se recuerda cuál descartó, no un
     * estado copiado en un efecto.
     */
    const flashedKey = flash?.store_api_key ?? null;
    const [dismissedKey, setDismissedKey] = useState<string | null>(null);
    const apiKey =
        flashedKey && flashedKey !== dismissedKey ? flashedKey : null;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tienda', href: storeSettings.edit(companyId).url },
        { title: 'Ajustes', href: storeSettings.edit(companyId).url },
    ];

    const formMethods = useStoreSettingsForm({ initialData: settings });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ajustes de tienda" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Ajustes de tienda
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Nombre, logo, contacto, catálogo y llave de acceso de la
                        tienda en línea
                    </p>
                </div>
                <StoreSettingsFormProvider value={{ ...formMethods, options }}>
                    <StoreSettingsForm />
                </StoreSettingsFormProvider>
            </div>
            <StoreApiKeyDialog
                apiKey={apiKey}
                onClose={() => setDismissedKey(flashedKey)}
            />
        </AppLayout>
    );
}
