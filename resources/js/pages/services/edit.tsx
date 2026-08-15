import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import services from '@/routes/services';
import type { BreadcrumbItem } from '@/types';
import { ServiceForm } from './components/ServiceForm';
import { ServiceFormProvider } from './contexts/ServiceFormContext';
import { useServiceForm } from './hooks/useServiceForm';
import type { Service } from './types/Service';

interface Props {
    service: Service;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ServicesEdit({ service }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Servicios', href: services.index(companyId).url },
        {
            title: service.name,
            href: services.show({ company: companyId, id: service.id }).url,
        },
        {
            title: 'Editar',
            href: services.edit({ company: companyId, id: service.id }).url,
        },
    ];

    const formMethods = useServiceForm({ mode: 'edit', initialData: service });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${service.name}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        services.show({ company: companyId, id: service.id }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {service.name}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar servicio
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Modifica los datos del servicio del catálogo
                    </p>
                </div>
                <ServiceFormProvider value={formMethods}>
                    <ServiceForm />
                </ServiceFormProvider>
            </div>
        </AppLayout>
    );
}
