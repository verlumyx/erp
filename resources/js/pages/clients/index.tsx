import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import clients from '@/routes/clients';
import type { BreadcrumbItem } from '@/types';
import { ClientList } from './components/ClientList';
import type { Client, ClientFilters, ClientMeta } from './types/Client';

interface Props {
    clients: Client[];
    meta: ClientMeta;
    filters: ClientFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ClientsIndex({ clients: items, meta, filters }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Clientes', href: clients.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Clientes" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <ClientList clients={items} meta={meta} filters={filters} />
            </div>
        </AppLayout>
    );
}
