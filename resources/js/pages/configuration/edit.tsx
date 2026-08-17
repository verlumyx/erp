import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import configurationRoutes from '@/routes/configuration';
import type { BreadcrumbItem, Configuration } from '@/types';
import { ConfigurationForm } from './components/ConfigurationForm';
import { ConfigurationFormProvider } from './contexts/ConfigurationFormContext';
import { useConfigurationForm } from './hooks/useConfigurationForm';

interface Props {
    configuration: Configuration;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ConfigurationEdit({ configuration }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Configuración',
            href: configurationRoutes.edit(companyId).url,
        },
    ];

    const formMethods = useConfigurationForm({ initialData: configuration });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Configuración
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Moneda, tasa de cambio y decimales con los que trabaja
                        la empresa
                    </p>
                </div>
                <ConfigurationFormProvider value={formMethods}>
                    <ConfigurationForm />
                </ConfigurationFormProvider>
            </div>
        </AppLayout>
    );
}
