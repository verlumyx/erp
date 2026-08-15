import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import services from '@/routes/services';
import type { BreadcrumbItem } from '@/types';
import { ServiceForm } from './components/ServiceForm';
import { ServiceFormProvider } from './contexts/ServiceFormContext';
import { useServiceForm } from './hooks/useServiceForm';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ServicesCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Servicios', href: services.index(companyId).url },
        { title: 'Nuevo servicio', href: services.create(companyId).url },
    ];

    const formMethods = useServiceForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo servicio" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={services.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Servicios
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo servicio
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Agrega una plataforma o servicio a tu catálogo
                    </p>
                </div>
                <ServiceFormProvider value={formMethods}>
                    <ServiceForm />
                </ServiceFormProvider>
            </div>
        </AppLayout>
    );
}
