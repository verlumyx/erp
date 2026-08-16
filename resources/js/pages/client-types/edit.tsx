import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import clientTypes from '@/routes/client-types';
import type { BreadcrumbItem } from '@/types';
import { ClientTypeForm } from './components/ClientTypeForm';
import { ClientTypeFormProvider } from './contexts/ClientTypeFormContext';
import { useClientTypeForm } from './hooks/useClientTypeForm';
import type { ClientType } from './types/ClientType';

interface Props {
    clientType: ClientType;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ClientTypesEdit({ clientType }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Tipos de cliente',
            href: clientTypes.index(companyId).url,
        },
        {
            title: clientType.name,
            href: clientTypes.show({
                company: companyId,
                id: clientType.id,
            }).url,
        },
        {
            title: 'Editar',
            href: clientTypes.edit({
                company: companyId,
                id: clientType.id,
            }).url,
        },
    ];

    const formMethods = useClientTypeForm({
        mode: 'edit',
        initialData: clientType,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${clientType.name}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        clientTypes.show({
                            company: companyId,
                            id: clientType.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {clientType.name}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar tipo de cliente
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Modifica el nombre o la descripción
                    </p>
                </div>
                <ClientTypeFormProvider value={formMethods}>
                    <ClientTypeForm />
                </ClientTypeFormProvider>
            </div>
        </AppLayout>
    );
}
