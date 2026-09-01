import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import clientAdvances from '@/routes/client-advances';
import type { BreadcrumbItem } from '@/types';
import { ClientAdvanceForm } from './components/ClientAdvanceForm';
import { ClientAdvanceFormProvider } from './contexts/ClientAdvanceFormContext';
import { useClientAdvanceForm } from './hooks/useClientAdvanceForm';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ClientAdvancesCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Anticipos de clientes',
            href: clientAdvances.index(companyId).url,
        },
        {
            title: 'Nuevo anticipo',
            href: clientAdvances.create(companyId).url,
        },
    ];

    const formMethods = useClientAdvanceForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo anticipo de cliente" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={clientAdvances.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Anticipos de clientes
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo anticipo de cliente
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra lo que el cliente adelanta antes de facturarle
                    </p>
                </div>
                <ClientAdvanceFormProvider value={formMethods}>
                    <ClientAdvanceForm />
                </ClientAdvanceFormProvider>
            </div>
        </AppLayout>
    );
}
