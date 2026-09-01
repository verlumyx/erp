import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import clientCollections from '@/routes/client-collections';
import type { BreadcrumbItem } from '@/types';
import { ClientCollectionForm } from './components/ClientCollectionForm';
import { ClientCollectionFormProvider } from './contexts/ClientCollectionFormContext';
import { useClientCollectionForm } from './hooks/useClientCollectionForm';
import type { ClientCollectionOptions } from './types/ClientCollection';

interface Props {
    options: ClientCollectionOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ClientCollectionsCreate({ options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Cobros a clientes',
            href: clientCollections.index(companyId).url,
        },
        {
            title: 'Nuevo cobro',
            href: clientCollections.create(companyId).url,
        },
    ];

    const formMethods = useClientCollectionForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo cobro a cliente" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={clientCollections.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Cobros a clientes
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo cobro a cliente
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra la entrada de dinero y repártela entre sus
                        facturas
                    </p>
                </div>
                <ClientCollectionFormProvider value={formMethods}>
                    <ClientCollectionForm options={options} />
                </ClientCollectionFormProvider>
            </div>
        </AppLayout>
    );
}
