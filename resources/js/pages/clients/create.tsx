import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import clients from '@/routes/clients';
import type { BreadcrumbItem } from '@/types';
import { ClientForm } from './components/ClientForm';
import { ClientFormProvider } from './contexts/ClientFormContext';
import { useClientForm } from './hooks/useClientForm';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ClientsCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Clientes', href: clients.index(companyId).url },
        { title: 'Nuevo cliente', href: clients.create(companyId).url },
    ];

    const formMethods = useClientForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo cliente" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={clients.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Clientes
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo cliente
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra los datos del cliente y, si quieres, su primer
                        perfil
                    </p>
                </div>
                <ClientFormProvider value={formMethods}>
                    <ClientForm />
                </ClientFormProvider>
            </div>
        </AppLayout>
    );
}
