import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import clientTypes from '@/routes/client-types';
import type { BreadcrumbItem } from '@/types';
import { ClientTypeForm } from './components/ClientTypeForm';
import { ClientTypeFormProvider } from './contexts/ClientTypeFormContext';
import { useClientTypeForm } from './hooks/useClientTypeForm';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ClientTypesCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Tipos de cliente',
            href: clientTypes.index(companyId).url,
        },
        {
            title: 'Nuevo tipo',
            href: clientTypes.create(companyId).url,
        },
    ];

    const formMethods = useClientTypeForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo tipo de cliente" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={clientTypes.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Tipos de cliente
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo tipo de cliente
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra la clasificación con la que agruparás a tus
                        clientes
                    </p>
                </div>
                <ClientTypeFormProvider value={formMethods}>
                    <ClientTypeForm />
                </ClientTypeFormProvider>
            </div>
        </AppLayout>
    );
}
